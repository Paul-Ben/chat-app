/**
 * EDMS-App Main JavaScript file
 * Handles global functionality and authentication
 */

// Check if user is logged in
function checkAuth() {
    const token = localStorage.getItem('edms_token');
    if (!token) {
        // Skip redirect if already on login or register page
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== 'login.html' && currentPage !== 'register.html' && currentPage !== 'index.html') {
            window.location.href = 'login.html';
        }
        return false;
    }
    return true;
}

// Logout function
function logout() {
    localStorage.removeItem('edms_token');
    localStorage.removeItem('edms_user');
    window.location.href = 'login.html';
}

// Register a new user
function registerUser(event) {
    event.preventDefault();
    
    const username = $('#username').val();
    const email = $('#email').val();
    const password = $('#password').val();
    const confirmPassword = $('#confirm-password').val();
    
    // Basic validation
    if (!username || !email || !password) {
        showAlert('Please fill in all required fields', 'danger');
        return;
    }
    
    if (password !== confirmPassword) {
        showAlert('Passwords do not match', 'danger');
        return;
    }
    
    // Show loading state
    const submitBtn = $('#register-btn');
    const originalText = submitBtn.html();
    submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...');
    submitBtn.prop('disabled', true);
    
    // Send registration request
    $.ajax({
        url: 'api/auth.php',
        type: 'POST',
        data: {
            action: 'register',
            username: username,
            email: email,
            password: password
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                showAlert('Registration successful! Redirecting to login...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                showAlert(response.message || 'Registration failed', 'danger');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
}

// Login function
function loginUser(event) {
    event.preventDefault();
    
    const email = $('#email').val();
    const password = $('#password').val();
    
    // Basic validation
    if (!email || !password) {
        showAlert('Please enter both email and password', 'danger');
        return;
    }
    
    // Show loading state
    const submitBtn = $('#login-btn');
    const originalText = submitBtn.html();
    submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Logging in...');
    submitBtn.prop('disabled', true);
    
    // Send login request
    $.ajax({
        url: 'api/auth.php',
        type: 'POST',
        data: {
            action: 'login',
            email: email,
            password: password
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                localStorage.setItem('edms_token', response.token);
                localStorage.setItem('edms_user', JSON.stringify(response.user));
                showAlert('Login successful! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = 'chat.html';
                }, 1000);
            } else {
                showAlert(response.message || 'Login failed', 'danger');
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
}

// Update user profile
function updateProfile(event) {
    event.preventDefault();
    
    if (!checkAuth()) return;
    
    const username = $('#username').val();
    const status = $('#status').val();
    const formData = new FormData(document.getElementById('profile-form'));
    formData.append('action', 'update');
    
    // Show loading state
    const submitBtn = $('#update-profile-btn');
    const originalText = submitBtn.html();
    submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...');
    submitBtn.prop('disabled', true);
    
    $.ajax({
        url: 'api/users.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                // Update local user data
                const userData = JSON.parse(localStorage.getItem('edms_user'));
                userData.username = username;
                userData.status = status;
                if (response.profile_pic) {
                    userData.profile_pic = response.profile_pic;
                }
                localStorage.setItem('edms_user', JSON.stringify(userData));
                
                showAlert('Profile updated successfully!', 'success');
            } else {
                showAlert(response.message || 'Failed to update profile', 'danger');
            }
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        },
        error: function() {
            showAlert('Server error. Please try again later.', 'danger');
            submitBtn.html(originalText);
            submitBtn.prop('disabled', false);
        }
    });
}

// Helper function to display alerts
function showAlert(message, type) {
    const alertDiv = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>`);
    
    $('#alert-container').html(alertDiv);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.alert('close');
    }, 5000);
}

// Initialize user data on profile page
function initProfile() {
    if (!checkAuth()) return;
    
    const userData = JSON.parse(localStorage.getItem('edms_user'));
    if (userData) {
        $('#username').val(userData.username);
        $('#email').val(userData.email);
        $('#status').val(userData.status || '');
        
        if (userData.profile_pic) {
            $('#profile-image').attr('src', userData.profile_pic);
        } else {
            // Default avatar using initial letter
            const initial = userData.username.charAt(0).toUpperCase();
            $('#profile-image').replaceWith(`
                <div class="large-profile-pic bg-primary d-flex align-items-center justify-content-center text-white">
                    <span style="font-size: 60px;">${initial}</span>
                </div>
            `);
        }
    }
}

// Initialize app
$(document).ready(function() {
    // Event listeners for auth forms
    $('#register-form').on('submit', registerUser);
    $('#login-form').on('submit', loginUser);
    $('#profile-form').on('submit', updateProfile);
    
    // Handle profile image preview
    $('#profile-pic').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#profile-image').attr('src', e.target.result);
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Initialize profile page if needed
    if (window.location.pathname.endsWith('profile.html')) {
        initProfile();
    }
    
    // Initialize main navigation
    $('#logout-btn').on('click', logout);
    
    // Check authentication for restricted pages
    if (window.location.pathname.endsWith('chat.html') || 
        window.location.pathname.endsWith('profile.html')) {
        checkAuth();
    }
});
