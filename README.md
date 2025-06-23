# Humpday Headshots 🎮

A comprehensive gaming profile and library management platform built with Laravel, featuring gamertag management across multiple platforms and a sophisticated game library system powered by the IGDB database.

## ✨ Features

### 🎯 Gamertag Management
- **Multi-Platform Support**: Steam, Xbox Live, PlayStation Network, Nintendo Online, Battle.net
- **Profile Organization**: Set primary gamertags per platform
- **Privacy Controls**: Public/private gamertag visibility
- **Display Names**: Custom friendly names for your profiles
- **Profile Links**: Automatic profile URL generation where applicable

### 🎮 Game Library Management
- **IGDB Integration**: Real-time game search with comprehensive metadata
- **Rich Game Data**: Cover art, screenshots, genres, release dates, and ratings
- **Personal Tracking**: 
  - Game status (Owned, Wishlist, Playing, Completed)
  - Personal ratings and notes
  - Hours played tracking
  - Purchase information (date, price, digital/physical)
  - Favorite game marking
- **Multi-Platform Games**: Track the same game across different platforms
- **Advanced Filtering**: Filter by status, platform, completion, and favorites

### 🔐 Authentication System
- **Secure Registration & Login**: Full authentication flow
- **Protected Routes**: Secure access to personal data
- **Session Management**: Persistent login sessions

### 🎨 Modern UI/UX
- **Tailwind CSS**: Beautiful, responsive design
- **Dark Mode Support**: Automatic theme switching
- **Mobile Responsive**: Optimized for all screen sizes
- **Interactive Elements**: Real-time search, AJAX forms, confirmation dialogs

## 🛠 Tech Stack

- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Blade Templates + Tailwind CSS 4.x
- **Build Tool**: Vite 6.x
- **Database**: MySQL/SQLite
- **API Integration**: IGDB (Internet Game Database) via Twitch API
- **Package Management**: Composer + NPM

## 📦 Key Dependencies

### PHP Packages
- `laravel/framework`: ^12.0
- `marcreichel/igdb-laravel`: ^5.3 (IGDB API integration)
- `laravel/tinker`: ^2.10.1

### JavaScript Packages
- `tailwindcss`: ^4.0.0
- `@tailwindcss/vite`: ^4.0.0
- `vite`: ^6.2.4
- `axios`: ^1.8.2

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL or SQLite
- Twitch Developer Account (for IGDB API access)

### Step 1: Clone the Repository
```bash
git clone <repository-url>
cd humpday-headshots
```

### Step 2: Install Dependencies
```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

### Step 3: Environment Configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### Step 4: Configure Environment Variables
Edit `.env` file with your settings:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=humpday_headshots
DB_USERNAME=your_username
DB_PASSWORD=your_password

# IGDB API Configuration (via Twitch)
TWITCH_CLIENT_ID=your_twitch_client_id
TWITCH_CLIENT_SECRET=your_twitch_client_secret
```

### Step 5: Database Setup
```bash
# Run migrations
php artisan migrate

# Seed the database (optional - creates sample data)
php artisan db:seed
```

### Step 6: Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### Step 7: Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## 🔑 IGDB API Setup

1. **Create Twitch Application**:
   - Go to [Twitch Developer Console](https://dev.twitch.tv/console/apps)
   - Create a new application
   - Copy the Client ID and Client Secret

2. **Configure Environment**:
   ```env
   TWITCH_CLIENT_ID=your_client_id_here
   TWITCH_CLIENT_SECRET=your_client_secret_here
   ```

3. **Test API Connection**:
   ```bash
   php artisan tinker
   # In Tinker:
   use MarcReichel\IGDBLaravel\Models\Game;
   Game::search('Cyberpunk')->limit(5)->get();
   ```

## 📁 Project Structure

```
app/
├── Http/Controllers/
│   ├── Auth/                    # Authentication controllers
│   ├── DashboardController.php  # Main dashboard
│   ├── GameController.php       # Game management
│   └── GamertagController.php   # Gamertag management
├── Models/
│   ├── User.php                 # User model with relationships
│   ├── Game.php                 # Game library model
│   └── Gamertag.php            # Gamertag model
└── ...

database/
├── migrations/                  # Database schema
├── factories/                   # Model factories
└── seeders/                    # Database seeders

resources/
├── views/
│   ├── layouts/                # Base templates
│   ├── auth/                   # Authentication views
│   ├── games/                  # Game management views
│   ├── gamertags/             # Gamertag management views
│   └── dashboard.blade.php     # Main dashboard
├── css/
│   └── app.css                 # Tailwind CSS
└── js/
    └── app.js                  # JavaScript entry point

routes/
└── web.php                     # Application routes
```

## 🎯 Usage

### Dashboard
- **Overview**: See your gamertags and game library stats
- **Quick Actions**: Fast access to add gamertags and games
- **Platform Coverage**: Visual overview of your gaming platforms

### Managing Gamertags
1. **Add Gamertag**: Click "Add Gamertag" from dashboard or navigation
2. **Select Platform**: Choose from supported gaming platforms
3. **Enter Details**: Provide your gamertag and optional display name
4. **Set Privacy**: Choose public/private visibility
5. **Primary Status**: Mark as primary for the platform

### Managing Game Library
1. **Search Games**: Use the IGDB-powered search to find games
2. **Select Game**: Click on a game from search results
3. **Add Details**: Specify your platform, status, and personal info
4. **Track Progress**: Update hours played, completion status, ratings

### Filtering & Organization
- **Status Filters**: View by owned, wishlist, playing, or completed
- **Platform Filters**: Filter by gaming platform
- **Search**: Find specific games in your library
- **Favorites**: Mark and filter your favorite games

## 🔒 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **Authentication Middleware**: Protected routes require login
- **Input Validation**: Comprehensive form validation
- **SQL Injection Prevention**: Eloquent ORM protects against SQL injection
- **XSS Protection**: Blade templates escape output by default

## 🚀 Deployment

### Production Environment
1. **Set Environment**: `APP_ENV=production` in `.env`
2. **Enable Optimizations**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   npm run build
   ```
3. **Set Permissions**: Ensure `storage/` and `bootstrap/cache/` are writable
4. **Configure Web Server**: Point document root to `public/` directory

### Environment Variables for Production
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Use strong database credentials
DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=strong_password

# Configure mail service
MAIL_MAILER=smtp
MAIL_HOST=your_smtp_host
MAIL_USERNAME=your_smtp_user
MAIL_PASSWORD=your_smtp_password
```

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 🛠 Development

### Adding New Gaming Platforms
1. **Update Constants**: Add platform to `Game::PLATFORMS` and `Gamertag::PLATFORMS`
2. **Update Views**: Add platform option to dropdown menus
3. **Test Integration**: Ensure platform-specific features work correctly

### Extending Game Data
1. **Migration**: Add new fields to games table
2. **Model**: Update `$fillable` and `$casts` in Game model
3. **Forms**: Add fields to create/edit forms
4. **Validation**: Update controller validation rules

### IGDB Data Enhancement
- **Additional Fields**: Expand IGDB data collection in `GameController@search`
- **Caching**: Configure IGDB cache settings in `config/igdb.php`
- **Error Handling**: Enhance API error handling and fallbacks

## 📋 Database Schema

### Users Table
- Standard Laravel user fields
- Relationships to gamertags and games

### Gamertags Table
- `user_id`: Foreign key to users
- `platform`: Gaming platform identifier
- `gamertag`: The actual gamertag/username
- `display_name`: Optional friendly name
- `is_public`: Visibility setting
- `is_primary`: Primary tag for platform
- `additional_data`: JSON field for platform-specific data

### Games Table
- `user_id`: Foreign key to users
- `igdb_id`: IGDB game identifier
- `name`: Game title
- `summary`: Game description
- `cover`: JSON field for cover art data
- `genres`: JSON field for genre information
- `platforms`: JSON field for platform data
- `status`: User's status (owned, wishlist, etc.)
- `platform`: User's specific platform
- `user_rating`: Personal rating (1-10)
- `notes`: Personal notes
- `hours_played`: Time invested
- `purchase_info`: Date and price paid

## 🤝 Contributing

1. **Fork the Repository**
2. **Create Feature Branch**: `git checkout -b feature/amazing-feature`
3. **Commit Changes**: `git commit -m 'Add amazing feature'`
4. **Push to Branch**: `git push origin feature/amazing-feature`
5. **Open Pull Request**

### Coding Standards
- **PSR-12**: Follow PHP coding standards
- **Laravel Conventions**: Use Laravel naming conventions
- **Comments**: Document complex logic
- **Testing**: Include tests for new features

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Laravel Framework**: The powerful PHP framework powering this application
- **IGDB**: Internet Game Database for comprehensive game data
- **Tailwind CSS**: For the beautiful, responsive design system
- **marcreichel/igdb-laravel**: Laravel package for IGDB integration

## 📞 Support

- **Issues**: Report bugs or request features via GitHub Issues
- **Documentation**: Refer to Laravel and IGDB documentation for detailed API information
- **Community**: Join Laravel community forums for general Laravel questions

## 🔮 Roadmap

- **Social Features**: Friend systems and game sharing
- **Achievements**: Track gaming achievements across platforms
- **Statistics**: Advanced analytics and gaming insights
- **API**: RESTful API for mobile app development
- **Import/Export**: Bulk import from Steam, PSN, etc.
- **Recommendations**: Game recommendation engine
- **Reviews**: Community game reviews and ratings

---

**Happy Gaming! 🎮**
