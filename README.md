# AstroEra API

The backend API for the AstroEra astrology platform, built with Laravel 9 and PHP 8.0. This API powers the mobile and web applications, handling user authentication, astrologer consultations (chat/call), pooja bookings, and more.

## 🛠 Technology Stack

- **Framework**: Laravel 9.x
- **Language**: PHP ^8.0
- **Database**: MySQL
- **Authentication**: JWT Auth (`tymon/jwt-auth`) & Laravel Passport
- **Storage**: AWS S3 (`league/flysystem-aws-s3-v3`)

## 🔌 Key Integrations

- **Exotel**: Cloud telephony integration for call masking and session management.
- **Firebase**: Push notifications for real-time updates (`FireBaseActionController`).
- **VideoSDK**: For live streaming and video conferencing.

## 🚀 Installation & Setup

1. **Clone the repository**
   ```bash
   git clone <repository_url>
   cd astroera-app-api
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Environment Configuration**
   Copy the example environment file and configure your credentials.
   ```bash
   cp .env.example .env
   ```
   Update the `.env` file with your configuration:
   - **Database**: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
   - **AWS S3**: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`
   - **Exotel**: `EXOTEL_SID`, `EXOTEL_TOKEN` (if env variables are used for this)
   - **Firebase**: Path to service account credentials.

4. **Generate Application Key**
   ```bash
   php artisan key:generate
   ```

5. **Run Migrations**
   ```bash
   php artisan migrate
   ```

6. **Serve the Application**
   ```bash
   php artisan serve
   ```

## 📂 Project Structure

### Routes (`routes/`)
The routing logic is split into modular files for better organization:
- **`api.php`**: The entry point for API routes. It dynamically loads routes from subdirectories.
- **`UserRoutes/*.php`**: Routes specific to end-users (Login, Homepage, Wallet, etc.).
- **`Pooja/*.php`**: Routes for Pooja bookings and categories.
- **`astrologer/*.php`**: Routes for the Astrologer dashboard and interactions.

### key Models (`app/Models/`)
- **`User/User.php`**: Core user management.
- **`Astrologer/Astrologer.php`** (implied): Astrologer profiles.
- **`Payment.php`**: Transaction logs and payment status.
- **`CallChatRequest.php`**: Manages the state of chat and call sessions between users and astrologers.

## 📡 API Overview

### Authentication
User authentication is handled via OTP and JWT tokens.
- `POST /api/user/login`: Login with phone number.
- `POST /api/user/verify-otp`: Verify OTP and get token.

### Features
- **Consultations**: Real-time logic for initiating and tracking calls/chats.
- **Webhooks**: `api.php` contains handlers for Exotel callbacks (e.g., `mtstatus`) to track call duration and status.

## 🧪 Testing

Run typical Laravel tests if available:
```bash
php artisan test
```
