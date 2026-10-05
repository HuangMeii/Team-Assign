<?php

use App\Imports\SubjectsImport;
use App\Models\Subject;
use Illuminate\Http\UploadedFile;

use function Tests\Support\make_user;

it('admin thêm môn học mới (không còn phân công giảng viên ở cấp môn)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_code' => 'CS' . uniqid(),
        'subject_name' => 'Môn học mới',
        'credits' => 3,
    ]);

    $response->assertSessionHas('success');
    expect(Subject::where('subject_name', 'Môn học mới')->exists())->toBeTrue();
});

it('admin có thể thêm môn học mà không nhập mã (mã tự sinh)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Lập trình Web',
        'credits' => 4,
    ]);

    $response->assertSessionHas('success');
    $subject = Subject::where('subject_name', 'Lập trình Web')->first();

    expect($subject)->not->toBeNull()
        ->and($subject->subject_code)->not->toBe('')
        ->and($subject->credits)->toBe(4);
});

it('admin không thể thêm môn học khi thiếu số tín chỉ', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $response = $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Môn học thiếu tín chỉ',
    ]);

    $response->assertSessionHasErrors('credits');
    expect(Subject::where('subject_name', 'Môn học thiếu tín chỉ')->exists())->toBeFalse();
});

it('admin không thể sửa mã môn học (subject_code bị giữ nguyên)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $subject = Subject::create([
        'subject_code' => 'LOCK001',
        'subject_name' => 'Môn học gốc',
        'credits' => 3,
    ]);

    $response = $this->actingAs($admin)->put(route('admin.subjects.update', $subject->subject_id), [
        'subject_code' => 'CHANGED01',
        'subject_name' => 'Tên đã sửa',
        'credits' => 2,
    ]);

    $response->assertSessionHas('success');

    $subject->refresh();
    expect($subject->subject_code)->toBe('LOCK001')
        ->and($subject->subject_name)->toBe('Tên đã sửa')
        ->and($subject->credits)->toBe(2);
});

it('môn học không còn gắn giảng viên phụ trách (phân công ở cấp lớp học phần)', function () {
    $subject = Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);

    expect($subject->refresh()->toArray())->not->toHaveKey('lecturer_id');
});

it('giảng viên thấy tất cả môn học trong form tạo lớp (phân công ở cấp lớp)', function () {
    $lecturer = make_user('lecturer', 'Giảng viên A');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);
    Subject::create(['subject_code' => 'SUB2', 'subject_name' => 'Môn 2']);

    $response = $this->actingAs($lecturer)->get(route('lecturer.classes.create'));

    $response->assertOk();
    $response->assertSee('Môn 1');
    $response->assertSee('Môn 2');
});

it('trang quản lý môn học không còn cột giảng viên phụ trách', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    Subject::create(['subject_code' => 'SUB1', 'subject_name' => 'Môn 1']);

    $response = $this->actingAs($admin)->get(route('admin.subjects.index'));

    $response->assertOk();
    $response->assertDontSee('Giảng viên phụ trách');
});

it('mã môn học luôn tự sinh, bỏ qua mã do client gửi lên', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_code' => 'HACKER99',
        'subject_name' => 'Mạng máy tính',
        'credits' => 3,
    ])->assertSessionHas('success');

    $subject = Subject::where('subject_name', 'Mạng máy tính')->first();

    expect($subject)->not->toBeNull()
        ->and($subject->subject_code)->not->toBe('HACKER99')
        ->and($subject->subject_code)->toMatch('/^[A-Z]{1,5}\d{3}$/');
});

it('form thêm môn học không còn ô nhập mã môn', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->get(route('admin.subjects.create'))
        ->assertOk()
        ->assertDontSee('name="subject_code"', false)
        ->assertSee('Mã môn học sẽ được hệ thống tự sinh');
});
it('admin chọn môn có 2 bài báo cáo (giữa kì + cuối kì)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Môn 2 bài',
        'credits' => 3,
        'report_count' => 2,
    ])->assertSessionHas('success');

    expect((int) Subject::where('subject_name', 'Môn 2 bài')->first()->report_count)->toBe(2);
});

it('môn học không gửi số bài báo cáo thì mặc định 1 (chỉ cuối kì)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Môn mặc định 1 bài',
        'credits' => 3,
    ])->assertSessionHas('success');

    expect((int) Subject::where('subject_name', 'Môn mặc định 1 bài')->first()->report_count)->toBe(1);
});

it('số bài báo cáo chỉ nhận 1 hoặc 2 (giá trị 3 bị chặn)', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $this->actingAs($admin)->post(route('admin.subjects.store'), [
        'subject_name' => 'Môn sai số bài',
        'credits' => 3,
        'report_count' => 3,
    ])->assertSessionHasErrors('report_count');

    expect(Subject::where('subject_name', 'Môn sai số bài')->exists())->toBeFalse();
});

it('sửa môn học không gửi số bài báo cáo thì giữ nguyên giá trị cũ', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $subject = Subject::create(['subject_code' => 'RPT001', 'subject_name' => 'Môn giữ nguyên', 'credits' => 3, 'report_count' => 2]);

    $this->actingAs($admin)->put(route('admin.subjects.update', $subject->subject_id), [
        'subject_name' => 'Môn giữ nguyên (đã sửa)',
        'credits' => 3,
    ])->assertSessionHas('success');

    expect((int) $subject->refresh()->report_count)->toBe(2);
});

it('form thêm/sửa môn học có chọn số bài báo cáo', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    $subject = Subject::create(['subject_code' => 'RPT002', 'subject_name' => 'Môn form', 'credits' => 3]);

    $this->actingAs($admin)->get(route('admin.subjects.create'))
        ->assertOk()
        ->assertSee('Số bài báo cáo của môn')
        ->assertSee('Chỉ cuối kì')
        ->assertSee('Giữa kì + Cuối kì');

    $this->actingAs($admin)->get(route('admin.subjects.edit', $subject->subject_id))
        ->assertOk()
        ->assertSee('Số bài báo cáo của môn');
});

it('import môn học: cột so_bai_bao_cao = 2 ⇒ 2 bài; thiếu cột/để trống ⇒ 1 bài', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $content = "ten_mon,so_tc,so_bai_bao_cao\n"
        . "Môn import 2 bài,3,2\n"
        . "Môn import 1 bài,3,\n"
        . "Môn import thiếu cột,3\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ])->assertSessionHas('success');

    expect((int) Subject::where('subject_name', 'Môn import 2 bài')->first()->report_count)->toBe(2)
        ->and((int) Subject::where('subject_name', 'Môn import 1 bài')->first()->report_count)->toBe(1)
        ->and((int) Subject::where('subject_name', 'Môn import thiếu cột')->first()->report_count)->toBe(1);
});

it('import môn học giá trị số bài sai (3) bị báo lỗi theo dòng', function () {
    $admin = make_user('admin', 'Admin hệ thống');

    $content = "ten_mon,so_tc,so_bai_bao_cao\nMôn sai import,3,3\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ]);

    // SubjectsImport dùng SkipsOnFailure nên dòng lỗi được đếm trong flash success dạng Lỗi: N
    expect(session('success'))->toContain('Lỗi')
        ->and(Subject::where('subject_name', 'Môn sai import')->exists())->toBeFalse();
});

it('import KHÔNG hạ môn đang có 2 bài xuống 1 khi ô số bài để trống', function () {
    $admin = make_user('admin', 'Admin hệ thống');
    Subject::create(['subject_code' => 'RPT003', 'subject_name' => 'Môn có sẵn 2 bài', 'credits' => 3, 'report_count' => 2]);

    $content = "ten_mon,so_tc,so_bai_bao_cao\nMôn có sẵn 2 bài,4,\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ])->assertSessionHas('success');

    $subject = Subject::where('subject_name', 'Môn có sẵn 2 bài')->first();

    expect((int) $subject->report_count)->toBe(2)
        ->and((int) $subject->credits)->toBe(4);
});

it('import môn học từ CSV phân cách TAB thành công', function () {
    $admin = make_user('admin', 'Admin Import Tab');

    // Đây đúng là định dạng file gây lỗi trước đây: cột phân cách bằng TAB (sao chép từ Excel).
    $content = "ten_mon\tso_tc\tso_bai_bao_cao\n"
        . "Ảo hóa và điện toán đám mây\t3\t2\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ])->assertSessionHas('success');

    $subject = Subject::where('subject_name', 'Ảo hóa và điện toán đám mây')->first();

    expect($subject)->not->toBeNull()
        ->and((int) $subject->credits)->toBe(3)
        ->and((int) $subject->report_count)->toBe(2)
        ->and($subject->subject_code)->not->toBe('');
});

it('import môn học từ CSV phân cách chấm phẩy thành công', function () {
    $admin = make_user('admin', 'Admin Import Semi');

    // Excel bản tiếng Việt/Âu hay xuất CSV phân cách bằng dấu chấm phẩy.
    $content = "ten_mon;so_tc;so_bai_bao_cao\n"
        . "Khai thác dữ liệu;3;1\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ])->assertSessionHas('success');

    $subject = Subject::where('subject_name', 'Khai thác dữ liệu')->first();

    expect($subject)->not->toBeNull()
        ->and((int) $subject->credits)->toBe(3)
        ->and((int) $subject->report_count)->toBe(1);
});

it('dòng trống ở cuối file import môn học không bị tính là dòng lỗi', function () {
    $admin = make_user('admin', 'Admin Import Blank');

    // Excel hay để lại các dòng trống ở cuối file (SubjectsImport có SkipsEmptyRows).
    $content = "ten_mon,so_tc,so_bai_bao_cao\n"
        . "Mã hóa và ứng dụng,3,2\n"
        . "\n\n\n";

    $this->actingAs($admin)->post(route('admin.subjects.import'), [
        'file' => UploadedFile::fake()->createWithContent('mon_hoc.csv', $content),
    ])->assertSessionHas('success');

    expect(session('success'))->toContain('Thêm mới: 1')
        ->and(session('success'))->not->toContain('Lỗi')
        ->and(Subject::where('subject_name', 'Mã hóa và ứng dụng')->exists())->toBeTrue();
});

it('dò đúng dấu phân cách của file CSV import môn học', function () {
    $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR;

    $tab = $dir . 'mon_hoc_tab.csv';
    file_put_contents($tab, "ten_mon\tso_tc\tso_bai_bao_cao\nẢo hóa\t3\t2\n");

    $comma = $dir . 'mon_hoc_comma.csv';
    file_put_contents($comma, "ten_mon,so_tc,so_bai_bao_cao\nẢo hóa,3,2\n");

    $semi = $dir . 'mon_hoc_semi.csv';
    file_put_contents($semi, "ten_mon;so_tc;so_bai_bao_cao\nẢo hóa;3;2\n");

    expect(SubjectsImport::detectCsvDelimiter($tab))->toBe("\t")
        ->and(SubjectsImport::detectCsvDelimiter($comma))->toBe(',')
        ->and(SubjectsImport::detectCsvDelimiter($semi))->toBe(';')
        ->and(SubjectsImport::detectCsvDelimiter($dir . 'khong-ton-tai.csv'))->toBeNull();

    @unlink($tab);
    @unlink($comma);
    @unlink($semi);
});