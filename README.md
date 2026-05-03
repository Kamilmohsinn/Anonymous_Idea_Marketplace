# Anonymous Idea Marketplace - Phase 1 Full Stack

This project now includes all major Phase 1 modules from your specification with real UI -> API -> DB flow.

Implemented modules:
- User authentication (login, registration, token-based password reset, session + JWT checks)
- Anonymous identity (pseudonym per idea/comment)
- Idea posting & management (full CRUD, draft/published, attachment upload, stage tracking)
- Voting, ranking & trending (time-decay based trending score)
- Collaboration requests (send/list/accept/decline)
- Private messaging chat (unlocked after accepted collaboration; encrypted-at-rest with AES via OpenSSL)
- Threaded comments with helpful/unhelpful reactions
- Advanced search/discovery (keyword/category/stage/sort)
- Similarity detection (non-blocking advisory)
- Reputation and badges (with leaderboard)
- Notifications (list + mark read)
- Reporting/moderation flow
- Admin control panel (stats, moderation queue, user status actions)

Recent completeness additions:
- True token-based password reset flow (`forgot_password.php` + `reset_password.php`)
- Profile settings module (`bio`, `email_notifications_enabled`, `public_profile_enabled`)
- Admin category management (create/delete categories)
- Comment pagination support (`page`, `per_page`)
- Chat file sharing attachments
- Hidden anonymous identity mapping table (`anonymous_identity_map`)

## Project Structure
```
/
├── api/
│   ├── auth_utils.php           # Session auth helper
│   ├── admin_utils.php          # Admin role guard
│   ├── check_auth.php           # Auth check endpoint
│   ├── chat_crypto.php          # Chat message encryption helper
│   ├── db.php                   # Shared PDO database connection
│   ├── forgot_password.php      # Password reset token request endpoint
│   ├── reset_password.php       # Password reset token consumption endpoint
│   ├── login.php                # Login endpoint
│   ├── logout.php               # Logout endpoint
│   ├── platform_utils.php       # Trending/similarity/reputation utilities
│   ├── register.php             # Registration endpoint
│   ├── admin/
│   │   ├── dashboard_stats.php
│   │   ├── reports.php
│   │   ├── recalculate_trending.php
│   │   ├── resolve_report.php
│   │   ├── users.php
│   │   └── update_user_status.php
│   ├── categories/
│   │   ├── create.php
│   │   ├── delete.php
│   │   └── list.php
│   ├── chat/
│   │   ├── open.php
│   │   ├── list.php
│   │   └── send.php
│   ├── profile/
│   │   ├── get.php
│   │   └── update.php
│   └── ideas/
│       ├── check_similarity.php
│       ├── create.php           # Create idea (auth required)
│       ├── list.php             # Read ideas (public / mine)
│       ├── get.php              # Read single idea details
│       ├── update.php           # Update own idea (auth required)
│       └── delete.php           # Delete own idea (auth required)
│   ├── votes/
│   │   └── cast.php             # Upvote/downvote endpoint
│   ├── comments/
│   │   ├── create.php           # Create comment
│   │   ├── react.php            # Helpful/unhelpful reaction
│   │   ├── list.php             # Read comments for idea
│   │   ├── update.php           # Update own comment
│   │   └── delete.php           # Delete own comment
│   ├── notifications/
│   │   ├── list.php
│   │   └── mark_read.php
│   ├── reports/
│   │   └── create.php
│   ├── reputation/
│   │   ├── leaderboard.php
│   │   └── me.php
│   └── collaboration/
│       ├── send.php             # Send collaboration request
│       ├── list.php             # List incoming/outgoing requests
│       └── respond.php          # Accept/decline request
├── pages/
│   ├── admin.html
│   ├── chat.html
│   ├── dashboard.html           # Protected CRUD UI for own ideas
│   ├── idea-details.html        # Public idea details screen
│   ├── notifications.html
│   ├── profile.html
│   └── search.html
├── database.sql                 # Database schema + seed data
├── index.html                   # Public live feed from DB
├── login.html                   # AJAX login form
└── styles.css                   # Shared styles
```

## Setup Instructions

### 1. Database Setup
1. Create a MySQL database named `anonymous_idea_marketplace`.
2. Run `database.sql` to create tables and seed sample data.

### 2. Configure DB Credentials
Edit `api/db.php`:
```php
$host = 'localhost';
$dbname = 'anonymous_idea_marketplace';
$username = 'root';
$password = '';
```

### 3. Run Locally (XAMPP on Windows)
1. Install XAMPP.
2. Start Apache and MySQL.
3. Place project folder inside `C:\xampp\htdocs\`.
4. Open `http://localhost/Anonymous_Idea_Marketplace/`.

## Screens Included
1. `index.html` - Landing page + live idea feed
2. `login.html` - Login + registration
3. `pages/dashboard.html` - Session-protected idea CRUD dashboard
4. `pages/idea-details.html` - Full idea detail view
5. `pages/search.html` - Advanced discovery and filtering
6. `pages/notifications.html` - Notification inbox
7. `pages/chat.html` - Private collaboration chat
8. `pages/profile.html` - Reputation and leaderboard
9. `pages/admin.html` - Moderation and user management

## API Endpoints

### Authentication
- `POST /api/login.php` (FormData: email, password)
	- Returns `token` (JWT)
- `POST /api/register.php` (FormData: email, password, role)
	- Returns `token` (JWT)
- `POST /api/forgot_password.php` (FormData: email)
	- Returns one-time reset token for local demo flow
- `POST /api/reset_password.php` (FormData: token, new_password)
- `GET /api/check_auth.php`
- `GET /api/logout.php`

### Ideas (CRUD)
- `POST /api/ideas/create.php`
	- FormData: title, category, problem_statement, proposed_solution, tags, stage, status, attachment
- `GET /api/ideas/list.php`
	- Supports filters: q, category, stage, sort(new/top/trending), status, mine, refresh_trending
- `GET /api/ideas/list.php?mine=1`
	- Logged-in user ideas only
- `GET /api/ideas/get.php?id={ideaId}`
	- Single idea details (includes similarity/trending/attachment)
- `POST /api/ideas/update.php`
	- FormData: id, title, category, problem_statement, proposed_solution, tags, stage, status, attachment
- `POST /api/ideas/delete.php`
	- FormData: id
- `POST /api/ideas/check_similarity.php`
	- FormData: title, problem_statement, proposed_solution, exclude_id(optional)

### Votes
- `POST /api/votes/cast.php`
	- FormData: idea_id, vote_type (1 or -1)

### Comments
- `POST /api/comments/create.php`
	- FormData: idea_id, content, parent_comment_id(optional)
- `GET /api/comments/list.php?idea_id={ideaId}&page={page}&per_page={perPage}`
- `POST /api/comments/update.php`
	- FormData: id, content
- `POST /api/comments/delete.php`
	- FormData: id
- `POST /api/comments/react.php`
	- FormData: comment_id, reaction(helpful/unhelpful)

### Collaboration Requests
- `POST /api/collaboration/send.php`
	- FormData: idea_id, message
- `GET /api/collaboration/list.php`
- `POST /api/collaboration/respond.php`
	- FormData: id, status (Accepted or Declined)

### Chat
- `GET /api/chat/open.php?request_id={collaborationRequestId}`
- `GET /api/chat/list.php?room_id={roomId}&after_id={lastMessageId}`
- `POST /api/chat/send.php`
	- FormData: room_id, message(optional if attachment provided), attachment(optional)

### Notifications
- `GET /api/notifications/list.php`
- `POST /api/notifications/mark_read.php`
	- FormData: id(optional)

### Reports / Moderation
- `POST /api/reports/create.php`
	- FormData: target_type(idea/comment), target_id, reason, details
- `GET /api/admin/reports.php`
- `POST /api/admin/resolve_report.php`
- `GET /api/admin/users.php`
- `POST /api/admin/update_user_status.php`
- `GET /api/admin/dashboard_stats.php`
- `POST /api/admin/recalculate_trending.php`

### Reputation / Categories
- `GET /api/reputation/me.php`
- `GET /api/reputation/leaderboard.php`
- `GET /api/categories/list.php`
- `POST /api/categories/create.php`
- `POST /api/categories/delete.php`

### Profile Settings
- `GET /api/profile/get.php`
- `POST /api/profile/update.php`
	- FormData: bio, email_notifications_enabled, public_profile_enabled

## Requirement Mapping
- Functional HTML pages: Yes
- PHP APIs for CRUD: Yes
- AJAX fetch + FormData: Yes
- Proper MySQL interaction: Yes (PDO + prepared statements)
- Session handling/login protection: Yes
- User-friendly UI without manual ID entry: Yes
- Real data flow UI -> API -> DB -> UI: Yes

## Hosting Requirement (Public URL)
Deploy on any free PHP + MySQL host, for example:
1. InfinityFree
2. 000webhost
3. AwardSpace

Deployment checklist:
1. Create hosting account and MySQL database.
2. Import `database.sql` in hosting phpMyAdmin.
3. Upload all project files to `htdocs`/`public_html`.
4. Update DB credentials in `api/db.php` using hosting DB values.
5. Open your public URL and test login + full CRUD from dashboard.

## Demo Credentials
- User: `test@example.com` / `password123`
- Admin: `admin@example.com` / `password123`

## Suggested Demo Flow for Submission
1. Register a new user or login with test account.
2. Create idea (Published + Draft modes, optional file attachment).
3. Verify similarity advisory and stage updates.
4. View idea in public feed and search page (Trending/New/Top).
5. Vote and comment from details screen (including replies/reactions).
6. Send collaboration request and accept it from receiver dashboard.
7. Open private chat from accepted request.
8. Submit a report and process it from admin panel.
9. Check notifications and reputation leaderboard.