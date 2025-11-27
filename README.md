# BlackNova Traders - Modern PHP Edition

A complete rewrite of BlackNova Traders using modern PHP 8.1+, PostgreSQL, and minimal dependencies.

## What's New in This Version

This is a ground-up rewrite of the classic BlackNova Traders game with modern technologies:

### Modern PHP Features
- **PHP 8.1+** with strict types and modern syntax
- **PSR-4 autoloading** with namespaces
- **Type declarations** throughout the codebase
- **No dependencies** - uses only built-in PHP features (PDO)

### Security Improvements
- **Password hashing** using `password_hash()` (bcrypt)
- **Prepared statements** for all database queries (SQL injection prevention)
- **CSRF protection** on all forms
- **XSS protection** with proper output escaping
- **Secure session handling** with regeneration
- **Security headers** (X-Frame-Options, X-Content-Type-Options, etc.)

### PostgreSQL Database
- **Native PostgreSQL** support with proper types
- **Foreign key constraints** for data integrity
- **Indexes** for performance
- **Transactions** where appropriate
- **Modern SQL** features (RETURNING, ON CONFLICT, etc.)

### Architecture
- **MVC-like structure** with separation of concerns
- **Clean routing** with RESTful URLs
- **Reusable components** (Database, Session, Router)
- **Modern views** with template inheritance

### Code Quality
- **No register_globals** workarounds
- **No SQL injection** vulnerabilities
- **No XSS** vulnerabilities
- **Proper error handling**
- **Clean, readable code**

## Requirements

- PHP 8.1 or higher
- PostgreSQL 12 or higher
- Apache/Nginx web server
- Composer (for autoloading)

## Installation

### 1. Clone or Download

```bash
git clone <repository-url> blacknova
cd blacknova
```

### 2. Install Dependencies

```bash
composer install
```

If you don't have composer, download it from https://getcomposer.org/

### 3. Configure Database

Copy the environment example file:

```bash
cp .env.example .env
```

Edit `.env` with your PostgreSQL credentials:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=blacknova
DB_USER=bnt
DB_PASS=your_secure_password
```

### 4. Create PostgreSQL Database

Create a PostgreSQL user and database:

```bash
sudo -u postgres psql
CREATE USER bnt WITH PASSWORD 'your_secure_password';
CREATE DATABASE blacknova OWNER bnt;
GRANT ALL PRIVILEGES ON DATABASE blacknova TO bnt;
\q
```

### 5. Initialize Database

Run the initialization script:

```bash
./scripts/init_db.sh
```

Or manually:

```bash
psql -h localhost -U bnt -d blacknova -f database/schema.sql
```

### 6. Create Universe

Generate sectors and planets:

```bash
php scripts/create_universe.php 1000 200
```

This creates 1000 sectors and 200 planets. Adjust as needed.

### 7. Configure Web Server

#### Apache

Point your virtual host to the `public` directory:

```apache
<VirtualHost *:80>
    ServerName blacknova.local
    DocumentRoot /path/to/blacknova/public

    <Directory /path/to/blacknova/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Enable mod_rewrite:

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

#### Nginx

```nginx
server {
    listen 80;
    server_name blacknova.local;
    root /path/to/blacknova/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 8. Set Permissions

```bash
chmod -R 755 public
chmod -R 750 config
```

### 9. Visit Your Game

Open your browser and visit:
- http://localhost (or your configured domain)
- Register a new account
- Start playing!

## Security Configuration

### Change Admin Password

Edit `config/config.php` and update the admin password hash:

```php
'admin_password' => password_hash('your_new_password', PASSWORD_DEFAULT),
```

### Generate Strong Passwords

```bash
php -r "echo password_hash('your_password', PASSWORD_DEFAULT) . PHP_EOL;"
```

## Directory Structure

```
blacknova/
├── config/              # Configuration files
│   └── config.php       # Main configuration
├── database/            # Database schemas
│   └── schema.sql       # PostgreSQL schema
├── public/              # Web root (point your web server here)
│   ├── index.php        # Application entry point
│   └── .htaccess        # Apache rewrite rules
├── scripts/             # Utility scripts
│   ├── create_universe.php  # Universe generator
│   └── init_db.sh       # Database initialization
├── src/                 # Application source code
│   ├── Controllers/     # Request handlers
│   ├── Core/            # Core framework components
│   ├── Models/          # Database models
│   └── Views/           # HTML templates
├── composer.json        # Composer configuration
└── README.md           # This file
```

## Key Features Implemented

### ✅ Completed Features

#### Core Systems
- User registration and authentication with bcrypt password hashing
- Session management with CSRF protection
- Secure database layer with prepared statements
- Modern responsive UI with template inheritance
- Score calculation and player statistics

#### Gameplay Features
- **Ship Types**: Four distinct ship classes (Scout, Merchant, Warship, Balanced)
  - Unique multipliers for cargo, turn costs, combat, and defense
  - Type-specific starting resources and bonuses
  - Strategic depth for different playstyles
- **Ship/Player Management**: Full ship status, equipment tracking, and character management
- **Sector Navigation**: Real-time movement through the universe with turn management
- **Port Trading**: Buy/sell commodities (ore, organics, goods, energy) with dynamic pricing
- **Planet System**:
  - Planet viewing and scanning
  - Planet colonization with colonist management
  - Resource production (ore, organics, goods, energy, fighters, torpedoes)
  - Planetary base construction and management
  - Transfer and production allocation controls

#### Combat & Defense
- **Combat System**:
  - Ship-to-ship combat with beam/torpedo attacks
  - Planet attacks with base destruction and capture mechanics
  - Defense deployment (mines and fighters)
  - Defense vs defense combat in sectors
  - Combat results with damage calculation
- **Attack Logs**:
  - Comprehensive combat history tracking
  - View attacks made and received
  - Combat statistics and filtering

#### Social Features
- **Teams/Alliances**:
  - Team creation and management
  - Invitation system with accept/decline
  - Team messaging and communication
  - Team rankings and statistics
  - Member management (kick, leave)
- **Player Messaging**:
  - Send/receive private messages between players
  - Inbox and sent message folders
  - Message read/unread tracking
  - Soft delete with trash functionality
  - Mark all as read feature

#### Economy & Progression
- **Intergalactic Bank (IGB)**:
  - Deposit and withdraw credits
  - Secure fund transfers between players
  - Loan system with configurable interest rates
  - Loan repayment tracking
  - Account balance management
- **Ship Upgrades**:
  - 10 upgradeable components (hull, engines, power, computer, sensors, beams, torpedo launchers, shields, armor, cloak)
  - Exponential cost scaling for balance
  - Upgrade and downgrade functionality
  - Component level tracking

#### Information & Rankings
- **Rankings System**:
  - Player rankings with 7 sort options (score, ships destroyed, planets, defenses, etc.)
  - Team rankings with aggregate statistics
  - Online/offline player detection
  - Efficiency calculations
  - Top 100 leaderboard with pagination
- **Player Info & Search**:
  - Comprehensive player profiles
  - Player search functionality
  - Team affiliation display
  - Combat statistics and achievements

### 🚧 Features to Implement

The original game had additional features that could be added:

- Trade routes (automated trading)
- Genesis torpedoes and terraforming
- Special devices (beacons, warp editors, etc.)
- News system (automated news generation)
- Scheduler for automated tasks (turn generation, production, etc.)
- Advanced admin panel features

## Feature Guide

### Getting Started

After registering and logging in, you'll start with a basic ship and limited resources. Your goal is to build your empire through trading, combat, and strategic alliances.

### Ship Types

Choose your ship class during registration to match your preferred playstyle. Each type has unique strengths and weaknesses:

**🚀 Scout - Fast & Efficient**
- **Best for**: Exploration, hit-and-run tactics, turn efficiency
- Cargo: 70% capacity (smaller holds)
- Turn Cost: 50% (moves cost half as much)
- Combat: 80% damage dealt
- Defense: 70% armor/shields
- Speed: 150% (fastest ship, better escape chance)
- Starting Resources: 2000 credits, 200 turns
- **Strategy**: Maximize movement and exploration. Use speed to avoid dangerous situations.

**🚢 Merchant - Trading Specialist**
- **Best for**: Trading, economic dominance, cargo hauling
- Cargo: 200% capacity (huge holds)
- Turn Cost: 120% (more expensive to move)
- Combat: 60% damage dealt
- Defense: 80% armor/shields
- Speed: 80% (slower movement)
- Starting Resources: 5000 credits, 100 turns, full cargo
- **Strategy**: Focus on trading runs. Avoid combat. Join a team for protection.

**⚔️ Warship - Combat Dominator**
- **Best for**: Combat, piracy, military operations
- Cargo: 60% capacity (limited holds)
- Turn Cost: 150% (expensive to operate)
- Combat: 150% damage dealt
- Defense: 140% armor/shields
- Speed: 90% (slightly slower)
- Starting Resources: 1000 credits, 150 turns, 10 torpedoes, 5 fighters
- **Strategy**: Hunt other players. Capture planets. Dominate through superior firepower.

**🛸 Balanced - All-Arounder**
- **Best for**: Flexible gameplay, learning the game, adaptation
- Cargo: 100% capacity
- Turn Cost: 100% (normal)
- Combat: 100% damage dealt
- Defense: 100% armor/shields
- Speed: 100% (normal)
- Starting Resources: 3000 credits, 150 turns, moderate cargo
- **Strategy**: Adapt to any situation. Switch between trading and combat as needed.

**Ship Type Impact**:
- Cargo capacity affects maximum commodities you can carry
- Turn costs determine how expensive movement is
- Combat/defense affects battle effectiveness
- Speed influences escape chances and initiative
- Starting resources shape your early game strategy

**Choosing Your Type**:
- New players: Balanced or Merchant (safer, easier to learn)
- Aggressive players: Warship (combat-focused)
- Experienced players: Scout (requires skill, very rewarding)
- Economic focus: Merchant (trading powerhouse)

### Trading & Economy

**Port Trading**: Visit ports to buy low and sell high. Each port specializes in different commodities:
- Ore ports: Buy ore cheap, sell goods/energy
- Organics ports: Buy organics cheap, sell ore/energy
- Goods ports: Buy goods cheap, sell ore/organics
- Energy ports: Buy energy cheap, sell other commodities

**Intergalactic Bank (IGB)**: Access from the main menu
- Deposit credits for safekeeping
- Withdraw when needed
- Transfer funds to other players (with configurable fee)
- Take loans to finance expansion (with interest)
- Repay loans to maintain good standing

### Combat & Defense

**Ship Combat**: Attack other ships in your sector
- Requires beams and/or torpedoes
- Success depends on ship upgrades and tactics
- Destroy ships to earn bounties and salvage
- View combat history in Attack Logs

**Planet Attacks**: Assault planets to capture them
- Attack planetary bases and defenses
- Capture planets for resource production
- Successful captures transfer ownership

**Sector Defense**: Deploy mines and fighters
- Place defensive units in sectors
- Automatic defense vs defense combat
- Retrieve defenses when needed
- View deployed defenses across all sectors

### Planets & Production

**Colonization**: Land on unowned planets to colonize
- Requires colonists from ports
- Each planet can produce resources
- Build bases for enhanced production

**Production Management**:
- Allocate colonists to different resources
- Produce: ore, organics, goods, energy, fighters, torpedoes
- Transfer resources to/from your ship
- Balance production for optimal efficiency

### Ship Upgrades

Enhance your ship with 10 upgradeable components:
- **Hull**: Increases ship armor and durability
- **Engines**: Improves speed and turn efficiency
- **Power**: Boosts overall ship performance
- **Computer**: Enhances targeting and calculations
- **Sensors**: Improves scanning and detection
- **Beams**: Increases beam weapon damage
- **Torpedo Launchers**: More torpedo capacity and damage
- **Shields**: Better defensive shielding
- **Armor**: Additional hull protection
- **Cloak**: Stealth capabilities

Costs increase exponentially with each level. You can also downgrade for partial refunds.

### Social Features

**Teams/Alliances**:
- Create or join teams
- Team messaging and coordination
- Shared team statistics
- Team rankings
- Invite system with accept/decline
- Team leadership and member management

**Player Messaging**:
- Send private messages to any player
- Inbox for received messages
- Sent folder to track outgoing messages
- Mark messages as read/unread
- Delete unwanted messages

**Player Profiles**:
- View detailed stats of any player
- Search for players by name
- See team affiliations
- View combat achievements
- Check online status

### Rankings & Statistics

**Player Rankings**: View top players sorted by:
- Overall score
- Ships destroyed
- Planets owned
- Deployed defenses
- Cash on hand
- Net worth
- Efficiency rating

**Team Rankings**: See top teams by:
- Combined team score
- Total team members
- Team assets and achievements

**Attack Logs**: Review your combat history
- Attacks you've made
- Attacks you've received
- Damage statistics
- Combat outcomes (success, failure, destroyed, escaped)
- Sector locations and timestamps

### Strategy Tips

1. **Start Small**: Begin with port trading to build capital
2. **Upgrade Wisely**: Focus on engines early for better movement
3. **Defend Yourself**: Deploy defenses in key sectors
4. **Join a Team**: Team play offers protection and coordination
5. **Diversify**: Balance trading, planet production, and combat
6. **Bank Your Credits**: Use IGB to protect wealth from attackers
7. **Scout First**: Check sector information before engaging
8. **Track Enemies**: Use attack logs to identify threats
9. **Plan Production**: Allocate planet colonists efficiently
10. **Stay Active**: Regular play helps you climb the rankings

## Migration from Old Version

If you're migrating from the old PHP/MySQL version:

1. **Do NOT** try to migrate the old database directly
2. **Export player data** if you want to preserve it
3. Start with a fresh database using the new schema
4. Manually recreate players with secure password hashes
5. The old passwords cannot be migrated (they weren't securely hashed)

## Development

### Adding New Routes

Edit `public/index.php`:

```php
$router->get('/myroute', fn() => $controller->myMethod());
$router->post('/myroute/:id', fn($id) => $controller->myMethod((int)$id));
```

### Creating New Models

Extend the `Model` base class:

```php
namespace BNT\Models;

class MyModel extends Model
{
    protected string $table = 'my_table';
    protected string $primaryKey = 'id';

    // Add your custom methods
}
```

### Creating New Controllers

```php
namespace BNT\Controllers;

class MyController
{
    public function __construct(
        private MyModel $model,
        private Session $session
    ) {}

    public function myAction(): void
    {
        // Your logic here
    }
}
```

### Creating Views

Create a new file in `src/Views/`:

```php
<?php
$title = 'My Page';
$showHeader = true;
ob_start();
?>

<h2>My Content</h2>
<!-- Your HTML here -->

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?>
```

## Configuration

All configuration is in `config/config.php`:

- Database settings
- Game parameters (starting credits, turns, etc.)
- Trading configuration
- Scheduler settings
- Security settings

## Performance Tips

1. **Enable opcache** in php.ini for production
2. **Use connection pooling** for PostgreSQL
3. **Add indexes** to frequently queried columns
4. **Enable gzip compression** in your web server
5. **Use CDN** for static assets if needed

## Security Best Practices

1. **Never commit** `.env` or sensitive config files
2. **Use HTTPS** in production
3. **Keep PHP updated** to the latest version
4. **Restrict database user** privileges
5. **Enable PostgreSQL SSL** connections
6. **Regular backups** of the database
7. **Monitor logs** for suspicious activity

## Troubleshooting

### Database Connection Errors

Check your `.env` file and ensure PostgreSQL is running:

```bash
sudo systemctl status postgresql
```

### 404 Errors on All Pages

Ensure mod_rewrite is enabled (Apache) or try_files is configured (Nginx).

### Permission Denied Errors

Check file permissions:

```bash
chmod -R 755 public
```

### Session Issues

Ensure PHP can write to the session directory:

```bash
sudo chmod 1777 /var/lib/php/sessions
```

## Credits

- **Original BlackNova Traders**: Created by the BlackNova development team
- **Modern Rewrite**: Completely rewritten with modern PHP and PostgreSQL
- **License**: Check original license terms

## Contributing

To contribute to this project:

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Add type declarations to all methods
5. Test your changes thoroughly
6. Submit a pull request

## Support

For issues, questions, or contributions:
- Check the documentation
- Review existing issues
- Create a new issue with details

## License

This is a modernized version of BlackNova Traders. Please respect the original license terms.

---

**Enjoy playing BlackNova Traders!** 🚀
