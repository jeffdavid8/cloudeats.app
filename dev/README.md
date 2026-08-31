# Development Files Directory

This directory contains development and testing files that have been moved out of the production codebase during Phase 3 modernization.

📚 **Related Documentation:**
- **[AI Development Notes](../AI-DEVELOPMENT-NOTES.md)** - Essential project patterns and context
- **[Phase 3 Completion Report](../docs/developer/phase3-modernization-completion.md)** - Full modernization details
- **[Developer Portal](../docs/developer/README.md)** - Complete technical documentation
- **[TODO List](../docs/TODO.md)** - Task tracking and project status

## Directory Structure

### `/debug/`
Contains diagnostic and debugging files:
- `debug_*.php` - Various debugging utilities
- These files were used for troubleshooting during development
- **Note**: These files may reference old file paths and require updates if used

### `/test/`
Contains development test files:
- `test_*.php` - Component and feature test files  
- `*_test.php` - Additional test files with different naming convention
- These were used for manual testing during development
- **Note**: For automated testing, see `/tests/` directory with PHPUnit framework

## Administrative Files (Kept in Production)

The following administrative files remain in the main codebase but should be used with caution:
- `reset_demo_user.php` - Demo user reset utility
- `html/reset_admin_password.php` - Admin password reset tool
- `html/reset_app_eventlogger.php` - Event logger reset utility

## Migration Notes

**Moved on**: November 7, 2025
**Reason**: Phase 3 code modernization - cleaning up development artifacts
**Impact**: Production codebase is now cleaner and more maintainable

## Usage Guidelines

1. **Development Files**: Files in this directory are for reference only
2. **Path Updates**: If you need to use any of these files, update the include paths to work with the new structure
3. **Modern Testing**: Use the PHPUnit framework in `/tests/` for new test development
4. **Debugging**: For production debugging, use the EventLogger system and admin tools

## File Cleanup Summary

- **50 total files** moved to development directory
- **28 test files** moved to `/dev/test/`
- **9 debug files** moved to `/dev/debug/`
- **10 additional test files** moved to `/dev/test/`
- **3 admin utilities** documented but kept in production

This cleanup reduces the production codebase size and improves maintainability while preserving development history.

## Cross-References

### Current Testing Infrastructure
- **Modern Tests**: `/tests/` directory with PHPUnit 10.5 framework
- **Test Configuration**: `phpunit.xml` in project root
- **Test Bootstrap**: `tests/bootstrap.php` for test environment setup
- **Test Runner**: `test-runner.php` for infrastructure verification

### Documentation Structure
- **[AI-DEVELOPMENT-NOTES.md](../AI-DEVELOPMENT-NOTES.md)** - Critical project memory and patterns
- **[docs/TODO.md](../docs/TODO.md)** - Current and completed tasks
- **[docs/developer/](../docs/developer/)** - Comprehensive technical documentation
- **[Phase 3 Report](../docs/developer/phase3-modernization-completion.md)** - Detailed completion report

### Development Guidelines
- **Include Patterns**: Use `__DIR__`-based absolute paths: `require_once __DIR__ . '/file.php'`
- **Autoloading**: Core classes use Composer autoloader (no manual includes)
- **Session Format**: `$_SESSION['user']` is array with username/role/is_admin keys
- **Testing**: Use PHPUnit framework instead of manual test files
- **Authentication**: Use unified AuthManager system (no AdminAuth)