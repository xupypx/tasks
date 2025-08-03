.
├── App
│   ├── Controllers
│   │   ├── AdminController.php
│   │   ├── Api
│   │   │   └── TaskApiController.php
│   │   ├── AuthController.php
│   │   ├── HomeController.php
│   │   ├── ProjectController.php
│   │   ├── SettingsController.php
│   │   ├── SolutionController.php
│   │   ├── TaskController.php
│   │   └── UserController.php
│   ├── Helpers
│   │   └── DiffHelper.php
│   ├── Middleware
│   │   ├── AuthMiddleware.php
│   │   ├── Dispatcher.php
│   │   └── RoleMiddleware.php
│   ├── Models
│   │   ├── Project.php
│   │   ├── Setting.php
│   │   ├── Solution.php
│   │   ├── Task.php
│   │   └── User.php
│   ├── Policies
│   │   └── AuthPolicy.php
│   ├── Services
│   │   └── AuthService.php
│   ├── Utils
│   │   └── EmailSender.php
│   └── Views
│       ├── admin
│       │   ├── reassign.php
│       │   ├── show.php
│       │   └── users.php
│       ├── auth
│       │   ├── forgot-password.php
│       │   ├── login.php
│       │   ├── logout.php
│       │   ├── register.php
│       │   └── reset-password.php
│       ├── components
│       │   ├── filter_sort.php
│       │   └── modal.php
│       ├── errors
│       │   ├── 403.php
│       │   ├── 404.php
│       │   ├── 500.php
│       │   └── 503.php
│       ├── home
│       │   ├── dashboard.php
│       │   ├── index.php
│       │   └── partials
│       │       ├── kanban-card.php
│       │       ├── task-card.php
│       │       └── task-list-item.php
│       ├── layouts
│       │   ├── dashboard.php
│       │   ├── main.php
│       │   └── partials
│       │       ├── alerts.php
│       │       ├── delete_modal.php
│       │       ├── footer.php
│       │       ├── header.php
│       │       ├── head_one.php
│       │       ├── pagination.php
│       │       └── pagination-placeholder.php
│       ├── projects
│       │   ├── create.php
│       │   ├── edit.php
│       │   ├── list.php
│       │   └── show.php
│       ├── settings
│       │   └── index.php
│       ├── solutions
│       │   └── edit.php
│       ├── tasks
│       │   ├── create.php
│       │   ├── edit.php
│       │   ├── list.php
│       │   ├── my_tasks.php
│       │   ├── show.php
│       │   └── success.php
│       └── users
│           ├── create.php
│           ├── edit.php
│           └── show.php
├── bootstrap.php
├── config
│   ├── config.php
│   ├── database.php
│   ├── pagination.php
│   └── smtp.php
├── core
│   ├── Autoloader.php
│   ├── FieldTypeInterface.php
│   ├── FieldType.php
│   ├── FieldTypeRegistry.php
│   ├── FileUploadField.php
│   ├── FilterService.php
│   ├── helpers.php
│   ├── MigrationService.php
│   ├── Model.php
│   ├── PaginationService.php
│   ├── RepeaterField.php
│   ├── Router.php
│   └── View.php
├── project-structure.txt
├── public
│   ├── assets
│   │   ├── css
│   │   │   ├── light-dark-sw.css
│   │   │   ├── login-dark.css
│   │   │   ├── main.css
│   │   │   ├── projects.css
│   │   │   ├── tasks.css
│   │   │   └── uikit.min.css
│   │   ├── img
│   │   │   ├── bg
│   │   │   │   ├── login
│   │   │   │   │   ├── 1044-1200x900.jpg
│   │   │   │   │   ├── 1044-1600x950.jpg
│   │   │   │   │   ├── 1044-2000x1050.jpg
│   │   │   │   │   ├── 1044-640x700.jpg
│   │   │   │   │   └── 1044-960x700.jpg
│   │   │   │   └── main
│   │   │   │       ├── 482-1200x900.jpg
│   │   │   │       ├── 482-2000x1000.jpg
│   │   │   │       ├── 482-640x700.jpg
│   │   │   │       └── 482-960x700.jpg
│   │   │   ├── cover-logo.svg
│   │   │   ├── fav
│   │   │   │   ├── apple-touch-icon.png
│   │   │   │   ├── favicon-96x96.png
│   │   │   │   ├── favicon.ico
│   │   │   │   ├── favicon.svg
│   │   │   │   ├── favicon.zip
│   │   │   │   ├── for-head.txt
│   │   │   │   ├── site.webmanifest
│   │   │   │   ├── web-app-manifest-192x192.png
│   │   │   │   └── web-app-manifest-512x512.png
│   │   │   ├── favi
│   │   │   │   ├── apple-touch-icon.png
│   │   │   │   ├── favicon-96x96.png
│   │   │   │   ├── favicon.ico
│   │   │   │   ├── favicon.svg
│   │   │   │   ├── favicon.zip
│   │   │   │   ├── for-head.txt
│   │   │   │   ├── site.webmanifest
│   │   │   │   ├── web-app-manifest-192x192.png
│   │   │   │   └── web-app-manifest-512x512.png
│   │   │   ├── favicon
│   │   │   │   ├── apple-touch-icon.png
│   │   │   │   ├── favicon-96x96.png
│   │   │   │   ├── favicon.ico
│   │   │   │   ├── favicon.svg
│   │   │   │   ├── favicon.zip
│   │   │   │   ├── for-head.txt
│   │   │   │   ├── site.webmanifest
│   │   │   │   ├── web-app-manifest-192x192.png
│   │   │   │   └── web-app-manifest-512x512.png
│   │   │   ├── favicon.ico
│   │   │   ├── favicon-src.svg
│   │   │   ├── favicon.svg
│   │   │   ├── login-logo.svg
│   │   │   ├── logo-src.svg
│   │   │   ├── log_o.svg
│   │   │   ├── logo.svg
│   │   │   └── logo_.svg
│   │   └── js
│   │       ├── dashboard.js
│   │       ├── login.js
│   │       ├── projects.js
│   │       ├── tasks.js
│   │       ├── theme-change.js
│   │       ├── uikit-icons.min.js
│   │       └── uikit.min.js
│   ├── fix-paths.php
│   ├── .htaccess
│   └── index.php
├── README.md
└── routes.php

37 directories, 146 files
