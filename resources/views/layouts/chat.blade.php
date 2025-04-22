<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') | WhatsApp Clone</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/whatsapp-favicon.png') }}" type="image/png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Segoe+UI:wght@400;500;600&display=swap" rel="stylesheet">
    
    <!-- Main CSS -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    
    @stack('styles')
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <!-- WhatsApp Main Container -->
    <div id="app">
        @auth
            <!-- Top Navigation (Mobile Only) -->
            <nav class="mobile-nav">
                <div class="mobile-nav-brand">
                    <img src="{{ asset('images/whatsapp-logo.png') }}" alt="WhatsApp" class="logo">
                </div>
                <div class="mobile-nav-actions">
                    <i class="fas fa-search"></i>
                    <i class="fas fa-ellipsis-v"></i>
                </div>
            </nav>
        @endauth
        
        <!-- Main Content -->
        <main class="main-content-wrapper">
            @yield('content')
        </main>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    
    <!-- Main JS -->
    <script src="{{ asset('js/app.js') }}"></script>
    
    @stack('scripts')
    
    <!-- WhatsApp-specific Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile back button functionality
            const backButton = document.querySelector('.back-button');
            if (backButton) {
                backButton.addEventListener('click', function() {
                    document.querySelector('.sidebar').style.display = 'block';
                    document.querySelector('.main-content').style.display = 'none';
                });
            }
            
            // Mark messages as read when chat is opened
            @if(request()->route('chat'))
                fetch(`/api/chats/{{ request()->route('chat')->id }}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
            @endif
        });
    </script>
</body>
</html>