# TinyURL Application

A URL shortening web application with user authentication using Firebase, MySQL, PHP, Tailwind CSS, and jQuery.

## Features

- User authentication with Firebase
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

### Database Setup

1. Create a MySQL database and import the schema:

```bash
mysql -u username -p < setup.sql
```

### Firebase Setup

1. Create a new Firebase project at [https://console.firebase.google.com/](https://console.firebase.google.com/)
2. Enable Email/Password authentication in the Firebase console
3. Get your Firebase configuration (apiKey, authDomain, etc.)
4. Update the Firebase configuration in `index.php`

### Configuration

1. Update the database connection settings in `config/config.php`:

```php
define('DB_HOST', 'your_db_host');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
define('DB_NAME', 'tinyurl_db');
```

2. Update the Firebase API key in `config/config.php`:

```php
define('FIREBASE_API_KEY', 'your_firebase_api_key');
```

3. Make sure the `BASE_URL` constant in `config/config.php` is set correctly for your environment.

### Web Server Configuration

Make sure Apache's mod_rewrite is enabled and AllowOverride is set to All in your Apache configuration.

## API Documentation

### Shorten URL

**Endpoint:** `/api/shorten.php`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `url`: The URL to shorten

**Example Request:**
```json
{
  "api_key": "your_api_key",
  "url": "https://example.com"
}
```

**Example Response:**
```json
{
  "success": true,
  "url_id": 123,
  "original_url": "https://example.com",
  "short_url": "http://yourdomain.com/abc123",
  "short_code": "abc123"
}
```

### Get URL Statistics

**Endpoint:** `/api/stats.php`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `short_code`: The short code of the URL

**Example Request:**
```json
{
  "api_key": "your_api_key",
  "short_code": "abc123"
}
```

**Example Response:**
```json
{
  "success": true,
  "url_id": 123,
  "original_url": "https://example.com",
  "short_url": "http://yourdomain.com/abc123",
  "short_code": "abc123",
  "created_at": "2023-01-01 12:00:00",
  "click_count": 42
}
```

### List URLs

**Endpoint:** `/api/list.php`
**Method:** POST
**Parameters:**
- `api_key`: Your API key
- `limit`: (Optional) Number of URLs to return (default: 10)
- `offset`: (Optional) Offset for pagination (default: 0)

**Example Request:**
```json
{
  "api_key": "your_api_key",
  "limit": 10,
  "offset": 0
}
```

**Example Response:**
```json
{
  "success": true,
  "total": 42,
  "limit": 10,
  "offset": 0,
  "urls": [
    {
      "id": 123,
      "original_url": "https://example.com",
      "short_url": "http://yourdomain.com/abc123",
      "short_code": "abc123",
      "created_at": "2023-01-01 12:00:00",
      "click_count": 42
    },
    // More URLs...
  ]
}
```

## License

This project is licensed under the MIT License.