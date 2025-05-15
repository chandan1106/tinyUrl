# TinyURL Application

A URL shortening web application with user authentication using Firebase, MySQL, PHP, Tailwind CSS, and jQuery.

## Features

- User authentication with Firebase (Email/Password and Google Sign-In)
- URL shortening with custom short codes
- URL click tracking and statistics
- API for programmatic access
- Responsive design with Tailwind CSS

## Setup Instructions

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server with mod_rewrite enabled
- Firebase account

### Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/tinyUrl.git
cd tinyUrl
```

2. Copy the environment example file and update with your credentials:
```bash
cp .env-example .env
```

3. Edit the `.env` file with your database and Firebase credentials:
```
DB_HOST=localhost
DB_USER=your_db_user
DB_PASS=your_db_password
DB_NAME=tinyurl_db
FIREBASE_API_KEY=your_firebase_api_key
FIREBASE_AUTH_DOMAIN=your-project.firebaseapp.com
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_STORAGE_BUCKET=your-project.appspot.com
FIREBASE_MESSAGING_SENDER_ID=your-messaging-sender-id
FIREBASE_APP_ID=your-app-id
FIREBASE_MEASUREMENT_ID=your-measurement-id
```

4. Import the database schema:
```bash
mysql -u username -p < setup.sql
```

5. Configure your web server to point to the project directory and ensure mod_rewrite is enabled.

### Web Server Configuration

Make sure Apache's mod_rewrite is enabled and AllowOverride is set to All in your Apache configuration.

## API Documentation

### Authentication

All API requests require an API key. Include your API key in the request body as `api_key`.

### Endpoints

#### 1. Shorten URL

**Endpoint:** `/api/shorten`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `url`: The URL to shorten

#### 2. Get URL Statistics

**Endpoint:** `/api/stats`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `short_code`: The short code of the URL

#### 3. List URLs

**Endpoint:** `/api/list`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `limit`: (Optional) Number of URLs to return (default: 10)
- `offset`: (Optional) Offset for pagination (default: 0)

## Security Notes

- Never commit the `.env` file to version control
- Keep your API keys and credentials private
- For production, set environment variables on your server instead of using a .env file
- Regularly update your dependencies and Firebase SDK

## License

This project is licensed under the MIT License.