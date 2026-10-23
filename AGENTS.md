# PhoneStore - Website Bán Điện Thoại Trực Tuyến

## Thông tin dự án
- **Tên dự án:** PhoneStore
- **Loại:** Dự án thực tập - Website bán điện thoại trực tuyến
- **Cảm hứng thiết kế:** CellphoneS (thương hiệu riêng: PhoneStore)
- **Công nghệ:** Laravel, PHP, MySQL, Bootstrap 5, JavaScript, jQuery

---

## Quy tắc phát triển

### 1. Kiến trúc & Cấu trúc
- **Tuân thủ Laravel MVC:** Controller xử lý logic, Model quản lý dữ liệu, View hiển thị giao diện.
- **Không vi phạm nguyên tắc MVC:** Không viết logic nghiệp vụ trong View, không truy vấn database trực tiếp trong Controller (dùng Model/Eloquent).
- **Tuân thủ cấu trúc thư mục chuẩn Laravel:** Routes, Controllers, Models, Views, Migrations, Seeders, Middleware đặt đúng vị trí.

### 2. Frontend
- **Sử dụng Blade Template Engine** cho tất cả giao diện.
- **Sử dụng Bootstrap 5** cho layout, responsive và UI components.
- **Sử dụng JavaScript & jQuery** cho tương tác phía client.
- **Không sử dụng framework JS khác** (React, Vue, Angular...) trừ khi được yêu cầu.
- **Đặt tên class CSS** theo quy ước rõ ràng, tránh xung đột.

### 3. Backend
- **Sử dụng MySQL** làm hệ quản trị cơ sở dữ liệu.
- **Sử dụng Eloquent ORM** để tương tác với database.
- **Sử dụng Migration** để quản lý cấu trúc database (không sửa trực tiếp database).
- **Sử dụng Seeder & Factory** để tạo dữ liệu mẫu.
- **Validation** phải được thực hiện ở cả Frontend và Backend.

### 4. Nguyên tắc an toàn
- ❌ **KHÔNG tự ý thay đổi công nghệ** đã thống nhất.
- ❌ **KHÔNG xóa hoặc ghi đè code đang hoạt động** mà không có lý do và xác nhận.
- ❌ **KHÔNG tự chạy lệnh xóa database** (`migrate:fresh`, `db:wipe`, `DROP`) mà không có xác nhận từ người dùng.
- ❌ **KHÔNG tự ý cài đặt package** mà không thông báo và giải thích lý do.
- ❌ **KHÔNG tuyên bố hoàn thành** khi chưa kiểm thử chức năng.

### 5. Quy trình phát triển
- ✅ **Phân tích yêu cầu** trước khi viết code.
- ✅ **Chia nhỏ chức năng** để triển khai từng bước (incremental development).
- ✅ **Kiểm thử sau mỗi bước** - đảm bảo chức năng hoạt động trước khi chuyển sang bước tiếp theo.
- ✅ **Commit logic rõ ràng** - mỗi commit nên tương ứng với một chức năng hoặc sửa lỗi cụ thể.
- ✅ **Comment code** ở những phần phức tạp để dễ hiểu và bảo trì.

### 6. Quy ước đặt tên
- **Model:** Số ít, PascalCase (VD: `Product`, `Category`, `Order`)
- **Controller:** PascalCase + "Controller" (VD: `ProductController`, `CartController`)
- **Migration:** snake_case, mô tả rõ ràng (VD: `create_products_table`)
- **Route:** kebab-case cho URL (VD: `/san-pham`, `/gio-hang`)
- **View (Blade):** snake_case, tổ chức theo thư mục (VD: `products/index.blade.php`)
- **Database table:** Số nhiều, snake_case (VD: `products`, `order_items`)

### 7. Bảo mật
- Sử dụng **CSRF Protection** cho tất cả form.
- Sử dụng **Eloquent** hoặc **Query Builder** (tránh raw query khi không cần thiết).
- **Hash password** bằng `bcrypt` (Laravel mặc định).
- **Validate & Sanitize** tất cả input từ người dùng.
- Phân quyền rõ ràng giữa **Admin** và **Khách hàng**.

---

## Cấu trúc thư mục dự án (dự kiến)

```
phone-store/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/           # Controllers cho trang quản trị
│   │   │   └── ...              # Controllers cho trang khách hàng
│   │   ├── Middleware/
│   │   └── Requests/            # Form Request Validation
│   └── Models/                  # Eloquent Models
├── database/
│   ├── migrations/              # Database migrations
│   ├── seeders/                 # Database seeders
│   └── factories/               # Model factories
├── resources/
│   └── views/
│       ├── layouts/             # Layout chính (master layout)
│       ├── admin/               # Views cho trang quản trị
│       ├── products/            # Views hiển thị sản phẩm
│       ├── cart/                # Views giỏ hàng
│       ├── auth/                # Views đăng nhập/đăng ký
│       └── ...
├── public/
│   ├── css/                     # CSS tùy chỉnh
│   ├── js/                      # JavaScript tùy chỉnh
│   └── images/                  # Hình ảnh sản phẩm
├── routes/
│   └── web.php                  # Định nghĩa routes
└── ...
```

---

## Ghi chú
- Dự án được phát triển tuần tự theo từng phase.
- Mỗi phase sẽ được kiểm thử trước khi chuyển sang phase tiếp theo.
- Mọi thay đổi lớn cần được thảo luận và xác nhận trước khi thực hiện.
