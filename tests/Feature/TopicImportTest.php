<?php

use App\Models\Topics;
use App\Imports\TopicsImport;
use Illuminate\Http\UploadedFile;

use function Tests\Support\make_class;
use function Tests\Support\make_subject;
use function Tests\Support\make_user;

/*
|--------------------------------------------------------------------------
| Import ĐỀ TÀI từ Excel/CSV — POST /topics/import
|--------------------------------------------------------------------------
| Cột file: ten_de_tai, mo_ta, muc_tieu, yeu_cau, ma_lop, so_tv_min, so_tv_max, han_dang_ky
| - Admin import được mọi lớp; giảng viên chỉ lớp mình phụ trách.
| - Tên đề tài trùng ⇒ BỎ QUA (không ghi đè).
| - Mã lớp sai / mô tả < 10 ký tự ⇒ lỗi theo dòng, KHÔNG tạo đề tài nào.
| Cần MySQL test theo phpunit.xml.
*/

/** Nội dung CSV cho import đề tài. */
function topics_import_csv(array $rows, string $delimiter = ','): string
{
    $csv = implode($delimiter, ['ten_de_tai', 'mo_ta', 'muc_tieu', 'yeu_cau', 'ma_lop', 'so_tv_min', 'so_tv_max', 'han_dang_ky']) . "\n";

    foreach ($rows as $row) {
        $csv .= implode($delimiter, $row) . "\n";
    }

    return $csv;
}

/** File CSV giả lập upload. */
function topics_import_file(array $rows, string $delimiter = ','): UploadedFile
{
    return UploadedFile::fake()->createWithContent('de_tai.csv', topics_import_csv($rows, $delimiter));
}

it('admin import đề tài từ CSV: gán đúng lớp, môn học và giảng viên phụ trách', function () {
    $admin = make_user('admin', 'Admin Import');
    $lecturer = make_user('lecturer', 'GV Import');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    $response = $this->actingAs($admin)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài import A', 'Mô tả đủ dài cho đề tài import A', 'Mục tiêu A', 'Yêu cầu A', $class->class_code, 2, 4, '2026-12-31'],
            ['Đề tài import B', 'Mô tả đủ dài cho đề tài import B', '', '', $class->class_code, '', '', ''],
        ]),
    ]);

    $response->assertRedirect(route('topics.index'))->assertSessionHas('success');

    $topicA = Topics::where('name', 'Đề tài import A')->first();

    expect($topicA)->not->toBeNull()
        ->and((int) $topicA->class_id)->toBe((int) $class->class_id)
        ->and((int) $topicA->subject_id)->toBe((int) $subject->subject_id)
        ->and($topicA->lecturer)->toBe($lecturer->name)
        ->and((int) $topicA->min_members)->toBe(2)
        ->and((int) $topicA->max_members)->toBe(4)
        ->and($topicA->goal)->toBe('Mục tiêu A')
        ->and($topicA->registration_deadline->format('Y-m-d'))->toBe('2026-12-31');

    // Dòng để trống min/max/hạn ⇒ dùng giá trị mặc định 2/4 và hạn 30 ngày
    $topicB = Topics::where('name', 'Đề tài import B')->first();

    expect($topicB)->not->toBeNull()
        ->and((int) $topicB->min_members)->toBe(2)
        ->and((int) $topicB->max_members)->toBe(4)
        ->and($topicB->registration_deadline->greaterThan(now()->addDays(29)))->toBeTrue();
});

it('tên đề tài đã tồn tại bị BỎ QUA, không ghi đè dữ liệu cũ', function () {
    $admin = make_user('admin', 'Admin Import 2');
    $lecturer = make_user('lecturer', 'GV Import 2');
    $subject = make_subject($lecturer);
    $class = make_class($subject, $lecturer);

    Topics::create([
        'name'                  => 'Đề tài đã tồn tại',
        'description'           => 'Mô tả gốc không được ghi đè',
        'class_id'              => $class->class_id,
        'subject_id'            => $subject->subject_id,
        'lecturer'              => $lecturer->name,
        'min_members'           => 2,
        'max_members'           => 4,
        'registration_deadline' => now()->addDays(30),
        'is_active'             => true,
    ]);

    $this->actingAs($admin)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài đã tồn tại', 'Mô tả MỚI cố tình ghi đè', '', '', $class->class_code, 3, 5, ''],
        ]),
    ])->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài đã tồn tại')->count())->toBe(1)
        ->and(Topics::where('name', 'Đề tài đã tồn tại')->first()->description)->toBe('Mô tả gốc không được ghi đè');
});

it('giảng viên chỉ import được cho lớp mình phụ trách', function () {
    $lecturerA = make_user('lecturer', 'GV A Import');
    $classA = make_class(make_subject($lecturerA), $lecturerA);

    $lecturerB = make_user('lecturer', 'GV B Import');
    $classB = make_class(make_subject($lecturerB), $lecturerB);

    // Lớp của mình -> thành công
    $this->actingAs($lecturerA)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài của GV A', 'Mô tả đủ dài cho đề tài của GV A', '', '', $classA->class_code, 2, 4, ''],
        ]),
    ])->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài của GV A')->exists())->toBeTrue();

    // Lớp của giảng viên khác -> bị chặn, KHÔNG tạo đề tài
    $this->actingAs($lecturerA)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài trái quyền', 'Mô tả đủ dài cho đề tài trái quyền', '', '', $classB->class_code, 2, 4, ''],
        ]),
    ])->assertRedirect(route('topics.import.form'))->assertSessionHas('error');

    expect(Topics::where('name', 'Đề tài trái quyền')->exists())->toBeFalse();
});

it('sinh viên không có quyền import đề tài', function () {
    $student = make_user('student', 'SV Import');

    $this->actingAs($student)->get(route('topics.import.form'))->assertForbidden();

    $this->actingAs($student)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài của sinh viên', 'Mô tả đủ dài cho đề tài của sinh viên', '', '', 'ABCDE', 2, 4, ''],
        ]),
    ])->assertForbidden();
});

it('mã lớp không tồn tại và mô tả quá ngắn đều bị báo lỗi theo dòng', function () {
    $admin = make_user('admin', 'Admin Import 3');
    $lecturer = make_user('lecturer', 'GV Import 3');
    $class = make_class(make_subject($lecturer), $lecturer);

    $response = $this->actingAs($admin)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài mã lớp sai', 'Mô tả đủ dài cho đề tài mã lớp sai', '', '', 'KHONGCO', 2, 4, ''],
            ['Đề tài mô tả ngắn', 'Ngắn', '', '', $class->class_code, 2, 4, ''],
        ]),
    ]);

    $response->assertRedirect(route('topics.import.form'))->assertSessionHas('error');

    expect(Topics::count())->toBe(0);
});

it('file sai định dạng bị từ chối', function () {
    $admin = make_user('admin', 'Admin Import 4');

    $this->actingAs($admin)->post(route('topics.import'), [
        'file' => UploadedFile::fake()->create('de_tai.txt', 1, 'text/plain'),
    ])->assertSessionHasErrors('file');
});

it('tải được file mẫu CSV của đề tài', function () {
    $admin = make_user('admin', 'Admin Import 5');

    $response = $this->actingAs($admin)->get(route('topics.download-template'));

    $response->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/csv')
        ->and($response->getContent())->toContain('ten_de_tai')
        ->and($response->getContent())->toContain('ma_lop');
});

it('admin mở được form import đề tài và thấy mã lớp để dùng trong file', function () {
    $admin = make_user('admin', 'Admin Import 6');
    $lecturer = make_user('lecturer', 'GV Import 6');
    $class = make_class(make_subject($lecturer), $lecturer);

    $this->actingAs($admin)
        ->get(route('topics.import.form'))
        ->assertOk()
        ->assertSee('Import Đề tài')
        ->assertSee($class->class_code)
        ->assertSee('ten_de_tai')
        ->assertSee('Dấu phân cách');
});

it('khách chưa đăng nhập không vào được form import', function () {
    $this->get(route('topics.import.form'))->assertRedirect(route('login'));
});

it('trang quản lý đề tài hiển thị nút Import đề tài', function () {
    $admin = make_user('admin', 'Admin Import 7');

    $this->actingAs($admin)
        ->get(route('topics.index'))
        ->assertOk()
        ->assertSee('Import đề tài')
        ->assertSee(route('topics.import.form'), false);
});

it('file CSV phân cách TAB (sao chép từ Excel) import được, không còn lỗi không được để trống cho mọi dòng', function () {
    $admin = make_user('admin', 'Admin Import Tab');
    $lecturer = make_user('lecturer', 'GV Import Tab');
    $class = make_class(make_subject($lecturer), $lecturer);

    // Đúng kiểu file người dùng gặp lỗi: phân cách TAB, mô tả có dấu phẩy, hạn dạng 31/12/2026
    $response = $this->actingAs($admin)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài TAB A', 'Ứng dụng cho phép mượn trả sách, tra cứu và quản lý độc giả', 'Mục tiêu TAB A', 'Biết PHP căn bản', $class->class_code, 2, 4, '31/12/2026'],
            ['Đề tài TAB B', 'Xây dựng pipeline đọc dữ liệu điểm và trực quan hoá kết quả', '', '', $class->class_code, '', '', ''],
        ], "\t"),
    ]);

    $response->assertRedirect(route('topics.index'))->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài TAB A')->exists())->toBeTrue()
        ->and(Topics::where('name', 'Đề tài TAB B')->exists())->toBeTrue()
        ->and(session('success'))->not->toContain('dòng lỗi');

    expect(Topics::where('name', 'Đề tài TAB A')->first()->registration_deadline->format('Y-m-d'))->toBe('2026-12-31');
});

it('file CSV phân cách dấu chấm phẩy import được', function () {
    $admin = make_user('admin', 'Admin Import Semi');
    $lecturer = make_user('lecturer', 'GV Import Semi');
    $class = make_class(make_subject($lecturer), $lecturer);

    $response = $this->actingAs($admin)->post(route('topics.import'), [
        'file' => topics_import_file([
            ['Đề tài chấm phẩy', 'Mô tả đủ dài cho đề tài chấm phẩy', 'Mục tiêu', 'Yêu cầu', $class->class_code, 2, 4, ''],
        ], ';'),
    ]);

    $response->assertRedirect(route('topics.index'))->assertSessionHas('success');

    expect(Topics::where('name', 'Đề tài chấm phẩy')->exists())->toBeTrue();
});

it('dòng trống ở cuối file không bị tính là dòng lỗi', function () {
    $admin = make_user('admin', 'Admin Import Blank');
    $lecturer = make_user('lecturer', 'GV Import Blank');
    $class = make_class(make_subject($lecturer), $lecturer);

    // Excel hay để lại các dòng trống ở cuối file
    $content = topics_import_csv([
        ['Đề tài có dòng trống cuối', 'Mô tả đủ dài cho đề tài có dòng trống cuối', '', '', $class->class_code, 2, 4, ''],
    ]) . "\n\n\n";

    $this->actingAs($admin)->post(route('topics.import'), [
        'file' => UploadedFile::fake()->createWithContent('de_tai.csv', $content),
    ])->assertRedirect(route('topics.index'))->assertSessionHas('success');

    expect(session('success'))->toContain('thêm mới 1')
        ->and(session('success'))->not->toContain('dòng lỗi')
        ->and(Topics::where('name', 'Đề tài có dòng trống cuối')->exists())->toBeTrue();
});

it('file sai tên cột ở dòng tiêu đề báo MỘT lỗi rõ ràng thay vì lỗi từng dòng', function () {
    $admin = make_user('admin', 'Admin Import Header');
    $lecturer = make_user('lecturer', 'GV Import Header');
    $class = make_class(make_subject($lecturer), $lecturer);

    // Tiêu đề Mô tả đề tài / Mã lớp học phần bị slug thành mo_ta_de_tai / ma_lop_hoc_phan
    $content = "Tên đề tài,Mô tả đề tài,Mục tiêu,Yêu cầu,Mã lớp học phần,Số TV min,Số TV max,Hạn đăng ký\n"
        . 'Đề tài sai tiêu đề,Mô tả đủ dài cho đề tài sai tiêu đề,,,' . $class->class_code . ",2,4,\n";

    $response = $this->actingAs($admin)->post(route('topics.import'), [
        'file' => UploadedFile::fake()->createWithContent('de_tai.csv', $content),
    ]);

    $response->assertRedirect(route('topics.import.form'))->assertSessionHas('error');

    expect(session('error'))->toContain('Không đọc được dòng tiêu đề')
        ->and(session('error'))->toContain('mo_ta')
        ->and(session('error'))->toContain('ma_lop')
        ->and(Topics::count())->toBe(0);
});

it('dò đúng dấu phân cách của file CSV (bỏ qua dòng trống, không nhầm dấu phẩy trong mô tả)', function () {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;

    $tab = $dir . 'de_tai_tab.csv';
    file_put_contents($tab, "ten_de_tai\tmo_ta\tma_lop\nĐề tài A\tMô tả có, dấu phẩy, trong nội dung\t9R9RX\n\n");

    $comma = $dir . 'de_tai_comma.csv';
    file_put_contents($comma, "ten_de_tai,mo_ta,ma_lop\nĐề tài B,Mô tả đủ dài cho đề tài B,9R9RX\n");

    $semi = $dir . 'de_tai_semi.csv';
    file_put_contents($semi, "ten_de_tai;mo_ta;ma_lop\nĐề tài C;Mô tả đủ dài cho đề tài C;9R9RX\n");

    expect(TopicsImport::detectCsvDelimiter($tab))->toBe("\t")
        ->and(TopicsImport::detectCsvDelimiter($comma))->toBe(',')
        ->and(TopicsImport::detectCsvDelimiter($semi))->toBe(';')
        ->and(TopicsImport::detectCsvDelimiter($dir . 'khong-ton-tai.csv'))->toBeNull();

    @unlink($tab);
    @unlink($comma);
    @unlink($semi);
});
