/**
 * Bó tối ưu D1 — marked (render Markdown cho chatbot) đóng gói Vite,
 * thay vì tải `marked.min.js` từ cdn.jsdelivr.net.
 *暴露 global `window.marked` để code inline trong chatbot.blade dùng được.
 */
import { marked } from 'marked';

window.marked = marked;