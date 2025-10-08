# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2024-12-19

### ⚡ BREAKING CHANGES

**Complete rewrite for CakePHP 5.x with modern PHP 8.1+ features**

- **Minimum Requirements**: PHP 8.1+, CakePHP 5.x
- **Template Location**: Moved from `src/Template/` to `templates/`
- **Template Extension**: Changed from `.ctp` to `.php`
- **Routing**: Completely rewritten using modern CakePHP 5.x closure-based routing
- **Service Architecture**: Business logic extracted to dedicated service classes

### 🚀 Added

- **Service Layer Architecture**:
  - `AclPermissionService`: Handles permission evaluation and matrix building
  - `AclSynchronizationService`: Manages ACO/ARO synchronization and database operations
- **Modern PHP Features**:
  - Strict type declarations (`declare(strict_types=1)`)
  - Typed properties and return types
  - PHP 8.1+ match expressions and nullsafe operators
- **Enhanced UI**:
  - Bootstrap 5-based responsive interface
  - Accessibility features (ARIA labels, semantic HTML5)
  - Modern card-based layout design
- **Improved Configuration**:
  - Default configuration values with automatic fallbacks
  - Enhanced pagination settings per ARO model
  - Better default ignore actions list
- **Development Tools**:
  - PHPUnit integration for testing
  - CakePHP CodeSniffer for code style
  - Comprehensive CLAUDE.md for AI assistance

### 🔄 Changed

- **Controller Refactoring**:
  - `AclController`: Split into smaller, focused methods
  - Better error handling with try-catch blocks
  - Service dependency injection
- **Component Modernization**:
  - `AclManagerComponent`: Rewritten with typed properties
  - Better separation of concerns
  - Modern PHP patterns and practices
- **Configuration Updates**:
  - Enhanced bootstrap configuration with defaults
  - Modern route definitions with proper parameter passing
  - Improved plugin loading mechanism
- **Template Improvements**:
  - Modern HTML5 semantic elements
  - Better accessibility and responsive design
  - Enhanced user experience with better visual feedback

### 🛠️ Technical Improvements

- **Clean Code Principles**:
  - Single Responsibility Principle adherence
  - Descriptive method and variable names
  - Elimination of code duplication
- **Architecture**:
  - Clear separation between controllers, services, and components
  - Better dependency management
  - Improved testability
- **Security**:
  - Enhanced input validation
  - Better error handling
  - Type safety improvements

### 📚 Documentation

- **Complete Documentation Rewrite**:
  - Updated README.md with CakePHP 5.x examples
  - Modern installation and configuration instructions
  - Enhanced troubleshooting section
- **Developer Guide**: Enhanced CLAUDE.md with modern architecture details

### 🔧 Internal Changes

- **Composer Updates**:
  - Package name changed to `ivanamat/cakephp5-aclmanager`
  - Updated dependencies for CakePHP 5.x
  - Added development dependencies
- **Code Quality**:
  - PSR-12 compliance
  - Strict typing throughout codebase
  - Modern exception handling

---

## Legacy Versions (CakePHP 3.x/4.x)

### [1.3] - 2016-xx-xx

#### Added
- `AclManager.hideDenied`: Hide plugins, controllers and actions denied in ACLs lists

#### Changed
- Enhanced `AclManager.ignoreActions` configuration
- Updated templates with better permission display
- Fixed ACO synchronization issues

### [1.2] - 2016-xx-xx

#### Added
- `AclManager.ignoreActions`: Ignore specific actions during ACO synchronization

### [1.1] - 2016-xx-xx

#### Changed
- Fixed ARO alias naming
- Updated plugin installer requirements
- Documentation improvements

#### Contributors
- [@pfuri](https://github.com/pfuri)
- [@tjanssl](https://github.com/tjanssl)

### [1.0.5] - 2016-xx-xx

#### Changed
- Fixed "Update ACOs" functionality
- Enhanced ARO model configuration
- Added admin prefix support