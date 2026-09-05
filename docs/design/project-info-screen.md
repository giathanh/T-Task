# Thiết kế màn hình: Thông tin dự án (Project Info)

Trạng thái: **Đã xác nhận — đang triển khai**
Branch liên quan: `feat/project-dashboard`

## 1. Mục tiêu

Màn hình hiển thị thông tin tổng quan của **một dự án cụ thể**: thông tin cơ bản, số lượng task, số lượng bug, và danh sách thành viên tham gia. Đây là màn hình "chi tiết dự án" (project detail/overview), truy cập khi người dùng chọn 1 project từ danh sách project.

## 2. Bối cảnh kỹ thuật hiện tại

- Stack: Laravel 13 + Blade + Alpine.js + Tailwind v4 (token MD3 định nghĩa ở `resources/css/app.css`).
- Hiện **chưa có** model `Project`, `Task`, `Bug`, hay bảng thành viên/pivot nào trong `database/migrations` — chỉ có bảng mặc định (`users`, `cache`, `jobs`).
- Vì vậy tài liệu này bao gồm cả đề xuất data model tối thiểu cần có để màn hình hoạt động, sẽ triển khai cùng lúc.

## 3. Phạm vi (Scope)

**Có trong màn hình này:**
- Thông tin cơ bản của project (tên, mô tả, trạng thái, ngày tạo/deadline, chủ sở hữu).
- Số liệu thống kê: tổng số task (kèm breakdown theo trạng thái), tổng số bug (kèm breakdown theo mức độ/trạng thái).
- Danh sách thành viên (avatar, tên, vai trò trong project).
- Điều hướng nhanh sang danh sách Task / danh sách Bug của project.

**Không thuộc phạm vi (để làm sau):**
- Màn hình danh sách chi tiết Task / Bug (kanban, filter...).
- Chỉnh sửa/tạo task, bug ngay trên màn hình này.
- Biểu đồ/báo cáo nâng cao (burndown, timeline).
- Quản lý quyền chi tiết theo từng thành viên (chỉ hiển thị, chưa có UI phân quyền).

## 4. Đối tượng dùng & quyền truy cập

- Chỉ thành viên của project (hoặc admin hệ thống) mới xem được màn hình.
- Một số hành động (Invite member, Edit project, Archive project) chỉ hiển thị cho owner/admin của project — thành viên thường chỉ xem.

## 5. Bố cục màn hình (Wireframe)

### Desktop (≥ 1024px)

```
┌──────────────────────────────────────────────────────────────────────┐
│  ← Back to Projects                                    [Edit] [⋮]    │
│                                                                        │
│  ┌────┐  Project Name                          Status: [In Progress] │
│  │Icon│  Mô tả ngắn của project...                                   │
│  └────┘  Owner: Nguyễn A · Tạo: 01/08/2026 · Deadline: 30/09/2026     │
├──────────────────────────────────────────────────────────────────────┤
│  ┌───────────────────┐ ┌───────────────────┐ ┌───────────────────┐   │
│  │  📋 Tasks         │ │  🐛 Bugs          │ │  👥 Members       │   │
│  │  24 total         │ │  8 total          │ │  6 người          │   │
│  │  ● 10 Todo        │ │  ● 3 Open         │ │  [+ Invite]       │   │
│  │  ● 9 In progress  │ │  ● 2 In progress  │ │                   │   │
│  │  ● 5 Done         │ │  ● 3 Resolved     │ │                   │   │
│  │  [Xem tasks →]    │ │  [Xem bugs →]     │ │                   │   │
│  └───────────────────┘ └───────────────────┘ └───────────────────┘   │
├──────────────────────────────────────────────────────────────────────┤
│  Thành viên (6)                                                      │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │ (Avt) Nguyễn A     Owner                                        │ │
│  │ (Avt) Trần B       Admin                                        │ │
│  │ (Avt) Lê C         Member                                       │ │
│  │ ...                                                    [Xem tất cả]│
│  └────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────┘
```

### Mobile (< 768px)

- Header co gọn: tên project + status badge, nút ⋮ (menu: Edit/Invite/Archive) đưa vào overflow menu.
- 3 stat card (Tasks/Bugs/Members) xếp dọc, full width, mỗi card có thể **tap để mở rộng** hoặc điều hướng sang trang con.
- Danh sách thành viên hiển thị tối đa 5, có link "Xem tất cả" mở modal/trang riêng.

## 6. Chi tiết từng khối (component)

### 6.1 Header thông tin project
| Trường | Nguồn dữ liệu | Ghi chú |
|---|---|---|
| Tên project | `projects.name` | Bắt buộc |
| Mô tả | `projects.description` | Có thể null, hiển thị "Chưa có mô tả" |
| Trạng thái | `projects.status` (enum: `Planning`, `InProgress`, `OnHold`, `Completed`, `Archived`) | Badge màu theo trạng thái |
| Owner | Thành viên có `project_user.role = admin` → `users.name` | Avatar + tên; có thể có nhiều admin |
| Ngày tạo | `projects.created_at` | Format `d/m/Y` |
| Deadline | `projects.due_date` | Có thể null; nếu quá hạn hiển thị màu cảnh báo (đỏ) |
| Nút Edit/⋮ | — | Chỉ hiện với owner/admin |

### 6.2 Stat card — Tasks
- Tổng số issue có `type = task` của project (`issues.project_id = ? AND issues.type = 'task'`).
- Breakdown theo `issues.status` (Open / In progress / Done).
- Bản đầu tiên: **không** có nút điều hướng "Xem tasks →" vì màn hình danh sách task chưa tồn tại (ngoài scope). Sẽ bổ sung khi có màn hình đó.

### 6.3 Stat card — Bugs
- Tổng số issue có `type = bug` của project.
- Breakdown theo `issues.status` (Open / In progress / Done) — có thể thêm màu theo `severity` (Low/Medium/High/Critical) ở bản sau nếu cần nhấn mạnh bug nghiêm trọng.
- Bản đầu tiên: không có nút điều hướng, tương tự Tasks.

### 6.4 Danh sách Members
- Lấy từ bảng pivot `project_user` (project_id, user_id, role).
- Vai trò gồm 3 cấp: **Admin**, **Leader**, **Member**. Một project có thể có nhiều Admin và nhiều Leader (không giới hạn 1 người/vai trò); Admin và Leader có thể là 2 user khác nhau.
- Hiển thị avatar (initials), tên, email, badge role (Admin/Leader/Member).
- Nút "+ Invite" chỉ hiện với role Admin hoặc Leader.
- "Xem tất cả" nếu > 5-6 thành viên (để sau nếu danh sách ngắn).

### 6.5 Trạng thái đặc biệt
- **Loading**: skeleton cho header + 3 stat card + list member.
- **Empty**: project chưa có task/bug nào → card hiển thị "Chưa có task nào" thay vì "0" trơ.
- **Không có quyền truy cập**: nếu user không thuộc project → 403 page.
- **Project không tồn tại**: 404.

## 7. Data model (đã triển khai)

```
projects
  id, name, description, status, due_date, created_by (FK users, nullable), created_at, updated_at

project_user (pivot)
  id, project_id (FK), user_id (FK), role (admin | leader | member), created_at, updated_at
  unique(project_id, user_id)

issues                              -- Task và Bug gộp chung 1 bảng, phân biệt bằng cột `type`
  id, project_id (FK), type (task | bug), title, description,
  status (open | in_progress | done), severity (low|medium|high|critical, nullable — chỉ dùng cho bug),
  assignee_id (FK users, nullable), created_by (FK users, nullable),
  created_at, updated_at
```

Enum PHP tương ứng: `App\Enums\ProjectStatus`, `App\Enums\ProjectRole`, `App\Enums\IssueType`, `App\Enums\IssueStatus`, `App\Enums\IssueSeverity`.

## 8. Route & Controller (đã triển khai)

```
GET /projects/{project}   → ProjectController@show   (màn hình này, tên route: projects.show)
```

`ProjectController@show`:
- Authorize qua `ProjectPolicy@view` (user phải là thành viên project, dựa trên bảng `project_user`).
- Trả `Project::issueStats()` — model method group-by `type` + `status` bằng 1 query, không dùng `withCount` nhiều lần.
- Trả danh sách members kèm role, và role của user hiện tại (`currentUserRole`) để Vue quyết định hiện nút "+ Invite".
- Route `GET /projects/{project}/tasks` và `/bugs` **chưa tạo** — nằm ngoài scope, xem mục 6.2/6.3.

## 9. UI/Style & Kỹ thuật render

- **Render bằng Vue 3** (single-file component, không dùng Inertia/Vue Router — project chưa cài 2 package này). Blade view (`resources/views/projects/show.blade.php`) chỉ đóng vai trò shell: bọc layout `x-app-layout`, nhúng dữ liệu qua `<script type="application/json">` (encode bằng `Js::from()`), rồi Vue đọc và mount vào `#project-show`.
- `resources/js/app.js` được tổng quát hoá để mount nhiều Vue component theo từng trang (dựa vào id phần tử), thay vì chỉ mount 1 `App.vue` demo như trước.
- Component chính: `resources/js/pages/ProjectShow.vue`.
- Dùng token MD3 sẵn có trong `app.css` (`bg-surface-container-*`, `text-on-surface-variant`, `shadow-elevation-1`...), không dùng màu Tailwind gray/indigo mặc định. Không cần thêm class `dark:` vì token MD3 tự đổi theo `prefers-color-scheme` ở cấp CSS variable.
- Badge trạng thái/role dùng cặp màu container MD3 (primary/tertiary/error container).
- **Không** làm progress bar / % hoàn thành ở bản này (theo quyết định của bạn) — để version sau.

## 10. Quyết định đã chốt

1. Task và Bug gộp chung bảng `issues`, phân biệt bằng cột `type`.
2. Vai trò thành viên: `admin`, `leader`, `member` — có thể có nhiều admin và nhiều leader khác nhau trong cùng 1 project.
3. Chưa làm progress bar / % hoàn thành ở bản đầu tiên.
4. Render bằng Vue (mount vào Blade shell), không dùng Inertia/Vue Router.
