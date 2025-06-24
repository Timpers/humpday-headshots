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

### 👥 Social Features
- **User Discovery**: Search and browse users by name, email, or gamertags
- **Connection System**: Send and manage friend requests
- **Friend Management**: Accept, decline, or block connection requests
- **Gamertag Browsing**: Discover gamers by platform and gaming profiles
- **Social Hub**: Dashboard with connection stats and recent activity
- **Real-time Notifications**: Badge indicators for pending friend requests

### 🎲 Group System
- **Group Creation**: Create public or private gaming groups
- **Group Management**: Invite members, manage roles, and moderate discussions
- **Group Discovery**: Browse and search for gaming groups
- **Group Invitations**: Send and manage group invitations
- **Membership Management**: Join, leave, and manage group memberships

### ⏰ Gaming Sessions
- **Session Scheduling**: Create gaming sessions with date, time, and game selection
- **IGDB Game Search**: Real-time game search and selection with rich game metadata
- **Manual Game Entry**: Fallback option for games not found in IGDB
- **Multi-Level Invitations**: Invite individual friends or entire groups
- **Email Notifications**: Automated email invitations with session details
- **Database Notifications**: In-app notifications for invitation tracking
- **Session Management**: Join, leave, and manage participant lists with real-time updates
- **Privacy Controls**: Public, friends-only, or invite-only sessions
- **Session Discovery**: Browse public sessions and upcoming events
- **Capacity Management**: Set maximum participants and track session availability
- **Real-time Status Tracking**: Live session status and participant management
- **Authorization System**: Host-only session editing and management controls
- **Responsive UI**: Beautiful, mobile-friendly session creation and management interface

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

### Key Features Implemented
- **Email Notifications**: Laravel Notifications with queue support
- **Session Management**: Complete CRUD operations with authorization
- **IGDB Integration**: Real-time game search with fallback manual entry
- **Database Design**: Comprehensive schema with proper relationships
- **Unit Testing**: Full test coverage for all gaming session models
- **Policy Authorization**: Secure host-only session management

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

# Mail Configuration (for gaming session invitations)
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

# Queue Configuration (for email notifications)
QUEUE_CONNECTION=database
```

### Step 5: Database Setup
```bash
# Run migrations
php artisan migrate

# Create notifications table (for gaming session invitations)
php artisan notifications:table
php artisan migrate

# Seed the database (optional - creates sample data)
php artisan db:seed
```

### Step 6: Queue Setup (for Email Notifications)
```bash
# Run queue migration
php artisan queue:table
php artisan migrate

# Start queue worker (in a separate terminal)
php artisan queue:work
```

### Step 7: Build Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

### Step 8: Start Development Server
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

**Note**: For gaming session email notifications to work, ensure the queue worker is running in a separate terminal:
```bash
php artisan queue:work
```

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
│   ├── Auth/                        # Authentication controllers
│   ├── DashboardController.php      # Main dashboard
│   ├── GameController.php           # Game management
│   ├── GamertagController.php       # Gamertag management
│   └── GamingSessionController.php  # Gaming session management
├── Models/
│   ├── User.php                     # User model with relationships
│   ├── Game.php                     # Game library model
│   ├── Gamertag.php                # Gamertag model
│   ├── GamingSession.php           # Gaming session model
│   ├── GamingSessionInvitation.php # Session invitation model
│   └── GamingSessionParticipant.php # Session participant model
├── Notifications/
│   └── GamingSessionInvitation.php  # Email notification for invitations
├── Policies/
│   └── GamingSessionPolicy.php      # Authorization for session management
└── ...

database/
├── migrations/                  # Database schema
├── factories/                   # Model factories
└── seeders/                    # Database seeders

resources/
├── views/
│   ├── layouts/                    # Base templates
│   ├── auth/                       # Authentication views
│   ├── games/                      # Game management views
│   ├── gamertags/                 # Gamertag management views
│   ├── gaming-sessions/           # Gaming session management views
│   └── dashboard.blade.php         # Main dashboard
├── css/
│   └── app.css                     # Tailwind CSS
└── js/
    └── app.js                      # JavaScript entry point

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

### Managing Gaming Sessions
1. **Create Session**: Navigate to Gaming Sessions → Create Session
2. **Game Selection**: 
   - Use IGDB search to find games with rich metadata
   - Use manual entry for games not in IGDB database
3. **Session Configuration**: 
   - Set date, time, and duration
   - Configure maximum participants
   - Choose privacy level (public, friends-only, invite-only)
   - Add session description and requirements
4. **Send Invitations**: 
   - Invite specific friends individually
   - Invite entire groups at once
   - Include personal messages with invitations
5. **Session Management**: 
   - View all your hosted and participating sessions
   - Join or leave sessions
   - Track session status and participant count
   - Edit or cancel sessions you host
6. **Notifications**: 
   - Receive email notifications for new invitations
   - Get in-app notifications for session updates
   - Track invitation responses and participant changes

### Social Features
1. **Browse Users**: Navigate to Social → Browse to see all users with public gamertags
2. **Search**: Use Social → Search to find specific users or gamertags
3. **Connect**: Send connection requests to other gamers
4. **Manage Requests**: Accept or decline incoming connection requests
5. **Friend Management**: View friends, remove connections, or block users
6. **Platform Filtering**: Filter users by specific gaming platforms

### Group System
1. **Create Group**: Navigate to Groups → Create
2. **Set Group Type**: Choose public or private
3. **Invite Members**: Add friends to the group
4. **Manage Roles**: Assign roles and permissions
5. **Moderate Discussions**: Manage group content and discussions

### Gaming Sessions
1. **Schedule Session**: Navigate to Gaming Sessions → Create
2. **Game Selection**: 
   - Search IGDB database for comprehensive game information
   - Use manual entry if game not found in IGDB
3. **Session Details**: 
   - Set scheduled date and time
   - Configure maximum participants
   - Choose privacy settings
4. **Invitation System**: 
   - Send invites to individual friends
   - Invite entire groups at once
   - Include custom messages with invitations
5. **Session Management**: 
   - Join, leave, or cancel sessions
   - Track participant status in real-time
   - Manage session capacity and availability
6. **Notifications**: 
   - Receive email notifications for invitations
   - Get in-app notifications for session updates

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

The application includes comprehensive test coverage for all gaming session functionality:

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature  # Integration tests
php artisan test --testsuite=Unit     # Unit tests

# Run gaming session specific tests
php artisan test tests/Unit/GamingSessionTest.php
php artisan test tests/Unit/GamingSessionInvitationTest.php
php artisan test tests/Unit/GamingSessionParticipantTest.php

# Run with coverage
php artisan test --coverage
```

### Test Coverage
- **Unit Tests**: 55+ tests covering all gaming session models
- **Model Relationships**: Comprehensive testing of Eloquent relationships
- **Business Logic**: Testing for session capacity, invitations, and participation
- **Authorization**: Policy testing for session management permissions
- **Validation**: Form validation and data integrity testing

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

### Gaming Sessions Tables

#### gaming_sessions
- `id`: Primary key
- `host_user_id`: Foreign key to users (session host)
- `title`: Session title
- `description`: Optional session description
- `game_name`: Name of the game to be played
- `game_data`: JSON field for IGDB game metadata
- `platform`: Gaming platform for the session
- `scheduled_at`: Date and time of the session
- `max_participants`: Maximum number of participants
- `status`: Session status (scheduled, active, completed, cancelled)
- `privacy`: Privacy setting (public, friends_only, invite_only)
- `requirements`: Optional session requirements/notes
- `timestamps`: Created and updated timestamps

#### gaming_session_invitations
- `id`: Primary key
- `gaming_session_id`: Foreign key to gaming_sessions
- `invited_user_id`: Foreign key to users (nullable for group invitations)
- `invited_group_id`: Foreign key to groups (nullable for user invitations)
- `invited_by_user_id`: Foreign key to users (invitation sender)
- `status`: Invitation status (pending, accepted, declined)
- `message`: Optional invitation message
- `responded_at`: Timestamp when invitation was responded to
- `timestamps`: Created and updated timestamps

#### gaming_session_participants
- `id`: Primary key
- `gaming_session_id`: Foreign key to gaming_sessions
- `user_id`: Foreign key to users
- `status`: Participation status (joined, left, kicked)
- `joined_at`: Timestamp when user joined
- `left_at`: Timestamp when user left (nullable)
- `notes`: Optional participant or host notes
- `timestamps`: Created and updated timestamps
- **Unique Constraint**: (`gaming_session_id`, `user_id`)

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

### Immediate Improvements
- **Enhanced Notifications**: Real-time browser notifications
- **Session Calendar**: Calendar view for gaming sessions
- **Advanced Filtering**: Enhanced session discovery filters
- **Group Session Management**: Bulk invitation improvements

### Medium-term Goals
- **Social Features**: Enhanced friend systems and game sharing
- **Achievements**: Track gaming achievements across platforms
- **Statistics**: Advanced analytics and gaming insights
- **Mobile App**: Native mobile application with API
- **Voice Integration**: Discord/TeamSpeak integration

### Long-term Vision
- **AI Recommendations**: Game and session recommendation engine
- **Community Features**: Forums, reviews, and user-generated content
- **Tournament System**: Organized gaming tournaments and competitions
- **Streaming Integration**: Twitch/YouTube live streaming features
- **Cross-Platform Sync**: Import from Steam, PSN, Xbox Live, etc.

---

**Happy Gaming! 🎮**
