<?php
// Troubleshooting help page
?>

<div class="help-breadcrumb">
    <a href="?app=help">Help Center</a> &gt; Troubleshooting
</div>

<h1>Troubleshooting Guide</h1>

<p>Resolve common issues and optimize your MediaBrain experience with these troubleshooting solutions and tips.</p>

<div class="help-toc">
    <h4><i class="fas fa-list"></i> Troubleshooting Topics</h4>
    <ul>
        <li><a href="#login-issues">Login & Authentication Issues</a></li>
        <li><a href="#performance-problems">Performance Problems</a></li>
        <li><a href="#application-errors">Application-Specific Errors</a></li>
        <li><a href="#browser-compatibility">Browser Compatibility</a></li>
        <li><a href="#mobile-issues">Mobile Device Issues</a></li>
        <li><a href="#data-sync">Data Sync & Storage Issues</a></li>
        <li><a href="#general-tips">General Tips & Maintenance</a></li>
    </ul>
</div>

<h2 id="login-issues">Login & Authentication Issues</h2>

<p>Resolve problems with accessing your MediaBrain account.</p>

<div class="help-expandable">
    <h3>Cannot Log In with Username/Password</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Verify Credentials</h4>
            <ul>
                <li>Double-check username spelling</li>
                <li>Ensure Caps Lock is not enabled</li>
                <li>Try typing password in a text editor first</li>
                <li>Check for extra spaces before/after text</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Browser Issues</h4>
            <ul>
                <li>Clear browser cache and cookies</li>
                <li>Disable browser extensions temporarily</li>
                <li>Try incognito/private browsing mode</li>
                <li>Test with a different browser</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">3</div>
            <h4>Account Status</h4>
            <ul>
                <li>Account may be temporarily disabled</li>
                <li>Contact system administrator if in organization</li>
                <li>Try password reset if available</li>
                <li>Check for system maintenance notifications</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>OAuth Login Problems (Facebook, Google, Apple)</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Provider-Specific Issues</h4>
            <div class="help-code-block">
# Facebook Login:
- Ensure you're logged into Facebook
- Check Facebook app permissions
- Clear Facebook cookies

# Google Login:
- Verify Google account is active
- Check Google account permissions
- Enable third-party cookies

# Apple Login:
- Ensure Apple ID is verified
- Check Safari privacy settings
- Allow pop-ups from MediaBrain
            </div>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Browser Configuration</h4>
            <ul>
                <li>Enable JavaScript</li>
                <li>Allow pop-ups from MediaBrain domain</li>
                <li>Enable third-party cookies</li>
                <li>Disable ad blockers temporarily</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>Session Timeout Issues</h3>
    <div class="expandable-content">
        <p>If you're frequently being logged out:</p>
        <ul>
            <li>Check browser privacy settings</li>
            <li>Ensure cookies are enabled</li>
            <li>Don't use "Clear cookies on exit" setting</li>
            <li>Add MediaBrain to browser exceptions</li>
            <li>Avoid using multiple tabs with different accounts</li>
        </ul>
    </div>
</div>

<h2 id="performance-problems">Performance Problems</h2>

<p>Improve MediaBrain's speed and responsiveness.</p>

<div class="help-expandable">
    <h3>Slow Loading Times</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Browser Optimization</h4>
            <div class="help-code-block">
# Clear Browser Data:
1. Open browser settings
2. Go to Privacy/Security section
3. Clear browsing data
4. Select: Cached images, Cookies, Site data
5. Choose "All time" timeframe
6. Clear data and restart browser
            </div>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Network Issues</h4>
            <ul>
                <li>Test internet connection speed</li>
                <li>Try wired connection instead of Wi-Fi</li>
                <li>Restart your router/modem</li>
                <li>Contact ISP if speeds are consistently slow</li>
                <li>Use different DNS servers (8.8.8.8, 1.1.1.1)</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">3</div>
            <h4>System Resources</h4>
            <ul>
                <li>Close unnecessary browser tabs</li>
                <li>Quit resource-intensive applications</li>
                <li>Restart your computer if needed</li>
                <li>Check available RAM and disk space</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>High Memory Usage</h3>
    <div class="expandable-content">
        <p>If MediaBrain is using too much memory:</p>
        <ul>
            <li>Refresh the page periodically</li>
            <li>Close unused application tabs</li>
            <li>Limit number of open MediaBrain tabs</li>
            <li>Update your browser to latest version</li>
            <li>Consider using a different browser</li>
        </ul>
    </div>
</div>

<h2 id="application-errors">Application-Specific Errors</h2>

<p>Resolve issues with individual MediaBrain applications.</p>

<div class="help-feature-grid">
    <div class="help-feature-card">
        <h4><i class="fas fa-book"></i> BibleBot Issues</h4>
        <ul>
            <li><strong>Search not working:</strong> Check spelling, try synonyms</li>
            <li><strong>Audio not playing:</strong> Enable audio, check volume</li>
            <li><strong>Bookmarks not saving:</strong> Ensure you're logged in</li>
            <li><strong>Slow search results:</strong> Clear browser cache</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="fas fa-utensils"></i> Recipes Issues</h4>
        <ul>
            <li><strong>Voice control not working:</strong> Allow microphone access</li>
            <li><strong>Images not uploading:</strong> Check file size/format</li>
            <li><strong>Recipes not saving:</strong> Verify login status</li>
            <li><strong>Timer not working:</strong> Enable notifications</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="fas fa-sun"></i> Weather Issues</h4>
        <ul>
            <li><strong>Location not found:</strong> Allow location access</li>
            <li><strong>Outdated weather:</strong> Refresh page or clear cache</li>
            <li><strong>Alerts not showing:</strong> Enable browser notifications</li>
            <li><strong>Wrong location:</strong> Manually set location</li>
        </ul>
    </div>
    
    <div class="help-feature-card">
        <h4><i class="fas fa-sitemap"></i> Ancestry Issues</h4>
        <ul>
            <li><strong>Family tree not loading:</strong> Check login, refresh page</li>
            <li><strong>Documents not uploading:</strong> Check file size limits</li>
            <li><strong>Changes not saving:</strong> Verify permissions</li>
            <li><strong>Sharing problems:</strong> Check privacy settings</li>
        </ul>
    </div>
</div>

<div class="help-expandable">
    <h3>Error Messages</h3>
    <div class="expandable-content">
        <div class="help-feature-grid">
            <div class="help-feature-card">
                <h4>"500 Internal Server Error"</h4>
                <ul>
                    <li>Temporary server issue</li>
                    <li>Wait a few minutes and try again</li>
                    <li>Clear browser cache</li>
                    <li>Contact admin if persistent</li>
                </ul>
            </div>
            
            <div class="help-feature-card">
                <h4>"403 Forbidden"</h4>
                <ul>
                    <li>Insufficient permissions</li>
                    <li>Log out and log back in</li>
                    <li>Contact admin for access</li>
                    <li>Check account status</li>
                </ul>
            </div>
            
            <div class="help-feature-card">
                <h4>"404 Not Found"</h4>
                <ul>
                    <li>Broken or outdated link</li>
                    <li>Check URL spelling</li>
                    <li>Navigate from main menu</li>
                    <li>Report broken links</li>
                </ul>
            </div>
            
            <div class="help-feature-card">
                <h4>"Network Error"</h4>
                <ul>
                    <li>Internet connection issue</li>
                    <li>Check network status</li>
                    <li>Try different network</li>
                    <li>Restart browser</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<h2 id="browser-compatibility">Browser Compatibility</h2>

<p>Ensure optimal MediaBrain experience across different browsers.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Recommended Browsers</h4>
    <div class="help-feature-grid">
        <div class="help-feature-card">
            <h4><i class="fab fa-chrome"></i> Google Chrome</h4>
            <p>Best overall experience</p>
            <ul>
                <li>Version 90+ recommended</li>
                <li>Full feature support</li>
                <li>Excellent performance</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fab fa-firefox"></i> Mozilla Firefox</h4>
            <p>Great privacy-focused option</p>
            <ul>
                <li>Version 88+ recommended</li>
                <li>Good feature support</li>
                <li>Strong privacy features</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fab fa-safari"></i> Safari</h4>
            <p>Optimized for macOS/iOS</p>
            <ul>
                <li>Version 14+ recommended</li>
                <li>Native Apple integration</li>
                <li>Good mobile experience</li>
            </ul>
        </div>
        
        <div class="help-feature-card">
            <h4><i class="fab fa-edge"></i> Microsoft Edge</h4>
            <p>Modern Windows browser</p>
            <ul>
                <li>Version 90+ recommended</li>
                <li>Good Windows integration</li>
                <li>Chromium-based reliability</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Required Browser Settings</h4>
    <div class="help-code-block">
# Essential Settings:
✅ JavaScript enabled
✅ Cookies enabled
✅ Local storage enabled
✅ Pop-ups allowed for MediaBrain
✅ Third-party cookies allowed
✅ Notifications enabled (optional)

# Recommended Settings:
✅ Hardware acceleration enabled
✅ Automatic updates enabled
✅ Security settings on default
✅ Ad blockers configured properly
    </div>
</div>

<div class="help-warning">
    <h5><i class="fas fa-exclamation-triangle"></i> Unsupported Browsers</h5>
    <p>These browsers may not work properly with MediaBrain:</p>
    <ul>
        <li>Internet Explorer (all versions)</li>
        <li>Very old browser versions (3+ years old)</li>
        <li>Heavily modified browsers</li>
        <li>Browsers with JavaScript disabled</li>
    </ul>
</div>

<h2 id="mobile-issues">Mobile Device Issues</h2>

<p>Resolve problems when using MediaBrain on smartphones and tablets.</p>

<div class="help-expandable">
    <h3>Touch Interface Problems</h3>
    <div class="expandable-content">
        <ul>
            <li><strong>Buttons not responding:</strong> Try tapping more precisely</li>
            <li><strong>Scrolling issues:</strong> Use one finger, scroll slowly</li>
            <li><strong>Zoom problems:</strong> Double-tap to reset zoom</li>
            <li><strong>Text selection:</strong> Long-press to select text</li>
        </ul>
    </div>
</div>

<div class="help-expandable">
    <h3>Mobile Browser Issues</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>iOS Safari Issues</h4>
            <ul>
                <li>Update iOS to latest version</li>
                <li>Clear Safari cache and data</li>
                <li>Disable content blockers temporarily</li>
                <li>Check privacy settings</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Android Chrome Issues</h4>
            <ul>
                <li>Update Chrome app</li>
                <li>Clear app cache and data</li>
                <li>Check data saver settings</li>
                <li>Ensure sufficient storage space</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>Voice Features on Mobile</h3>
    <div class="expandable-content">
        <p>For voice control in Recipes app:</p>
        <ul>
            <li>Allow microphone permissions</li>
            <li>Ensure quiet environment</li>
            <li>Speak clearly and at normal volume</li>
            <li>Check device microphone isn't blocked</li>
            <li>Try using headphones with microphone</li>
        </ul>
    </div>
</div>

<h2 id="data-sync">Data Sync & Storage Issues</h2>

<p>Resolve problems with saving and syncing your data.</p>

<div class="help-expandable">
    <h3>Data Not Saving</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>Authentication Check</h4>
            <ul>
                <li>Verify you're still logged in</li>
                <li>Check session hasn't expired</li>
                <li>Log out and log back in</li>
                <li>Ensure account has proper permissions</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Browser Storage</h4>
            <ul>
                <li>Enable local storage in browser</li>
                <li>Clear browser cache</li>
                <li>Disable private/incognito mode</li>
                <li>Check available disk space</li>
            </ul>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">3</div>
            <h4>Network Issues</h4>
            <ul>
                <li>Check internet connection stability</li>
                <li>Try saving smaller amounts of data</li>
                <li>Wait for network issues to resolve</li>
                <li>Use wired connection if possible</li>
            </ul>
        </div>
    </div>
</div>

<div class="help-expandable">
    <h3>File Upload Problems</h3>
    <div class="expandable-content">
        <div class="help-step">
            <div class="help-step-number">1</div>
            <h4>File Size and Format</h4>
            <div class="help-code-block">
# Image Upload Limits:
- Maximum size: 10MB per file
- Supported formats: JPG, PNG, WebP, GIF
- Recommended resolution: 1200x800 pixels

# Document Upload Limits:
- Maximum size: 50MB per file
- Supported formats: PDF, DOC, DOCX, TXT

# Tips:
- Compress large images before uploading
- Use online compression tools if needed
- Split large documents into smaller files
            </div>
        </div>
        
        <div class="help-step">
            <div class="help-step-number">2</div>
            <h4>Upload Troubleshooting</h4>
            <ul>
                <li>Try uploading files one at a time</li>
                <li>Check file isn't corrupted</li>
                <li>Ensure stable internet connection</li>
                <li>Try different file format</li>
                <li>Use different browser if issues persist</li>
            </ul>
        </div>
    </div>
</div>

<h2 id="general-tips">General Tips & Maintenance</h2>

<p>Keep MediaBrain running smoothly with regular maintenance.</p>

<div class="help-step">
    <div class="help-step-number">1</div>
    <h4>Regular Maintenance Tasks</h4>
    <div class="help-code-block">
# Weekly:
- Clear browser cache
- Check for browser updates
- Restart browser completely

# Monthly:
- Update browser to latest version
- Review saved passwords
- Clean up bookmarks and data

# As Needed:
- Export important data for backup
- Review privacy settings
- Clean up old files and documents
    </div>
</div>

<div class="help-step">
    <div class="help-step-number">2</div>
    <h4>Performance Optimization</h4>
    <ul>
        <li><strong>Use latest browser versions</strong> for best performance</li>
        <li><strong>Enable hardware acceleration</strong> in browser settings</li>
        <li><strong>Close unused tabs</strong> to free up memory</li>
        <li><strong>Disable unnecessary extensions</strong> that slow browsing</li>
        <li><strong>Use wired internet</strong> for best connectivity</li>
        <li><strong>Keep operating system updated</strong> for security</li>
    </ul>
</div>

<div class="help-step">
    <div class="help-step-number">3</div>
    <h4>When to Contact Support</h4>
    <p>Contact your system administrator or IT support if:</p>
    <ul>
        <li>Persistent login issues after trying all solutions</li>
        <li>Data loss or corruption</li>
        <li>System-wide performance problems</li>
        <li>Security concerns or suspicious activity</li>
        <li>Feature requests or bug reports</li>
        <li>Account permissions or access issues</li>
    </ul>
</div>

<div class="help-info">
    <h5><i class="fas fa-info-circle"></i> Reporting Issues</h5>
    <p>When reporting problems, include:</p>
    <ul>
        <li>Browser name and version</li>
        <li>Operating system</li>
        <li>Steps to reproduce the issue</li>
        <li>Error messages (exact text)</li>
        <li>Screenshots if helpful</li>
        <li>Time when problem occurred</li>
    </ul>
</div>

<div class="help-success">
    <h5><i class="fas fa-check-circle"></i> Prevention Tips</h5>
    <ul>
    <li><i class="fas fa-check"></i> Keep browsers updated automatically</li>
    <li><i class="fas fa-check"></i> Use strong, unique passwords</li>
    <li><i class="fas fa-check"></i> Enable two-factor authentication when available</li>
    <li><i class="fas fa-check"></i> Regularly backup important data</li>
    <li><i class="fas fa-check"></i> Use reputable antivirus software</li>
    <li><i class="fas fa-check"></i> Be cautious with browser extensions</li>
    <li><i class="fas fa-check"></i> Monitor system performance regularly</li>
    <li><i class="fas fa-check"></i> Follow security best practices</li>
    </ul>
</div>

<div style="margin-top: 40px; text-align: center;">
    <h3>Still Need Help?</h3>
    <p>If these solutions don't resolve your issue, try these resources:</p>
    <div style="margin: 20px 0;">
        <a href="?app=help&section=setup" class="btn">Setup Guide</a>
        <a href="?app=help" class="btn">Help Center Home</a>
        <?php if (isset($userRole) && $userRole === 'admin'): ?>
        <a href="?app=admin" class="btn" target="_blank">Admin Panel</a>
        <?php endif; ?>
    </div>
</div>