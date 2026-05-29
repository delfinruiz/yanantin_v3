<nav class="bg-white dark:bg-gray-800 shadow sticky top-0 z-50 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-5xl mx-auto px-6 flex justify-center items-center h-24 relative">
        <a href="/" class="flex items-center">
            @if($tenant->logoLightUrl())
                <img src="{{ $tenant->logoLightUrl() }}" alt="{{ $tenant->name }}" class="h-28 w-auto object-contain block dark:hidden">
            @endif
            @if($tenant->logoDarkUrl())
                <img src="{{ $tenant->logoDarkUrl() }}" alt="{{ $tenant->name }}" class="h-28 w-auto object-contain hidden dark:block">
            @endif
        </a>
        <button
            id="theme-toggle-btn"
            class="tlp-toggle-btn"
            onclick="var d=document.documentElement,b=document.body,p=this.querySelector('.tlp-toggle-dot'),isDark=d.classList.contains('dark');if(isDark){localStorage.setItem('theme','light');d.classList.remove('dark');b.style.backgroundColor='';p.style.left='5px'}else{localStorage.setItem('theme','dark');d.classList.add('dark');b.style.backgroundColor='#070919';p.style.left='35px'}"
            style="position:fixed;top:1rem;right:1rem;z-index:60;display:flex;align-items:center;padding:0;border-radius:9999px;width:64px;height:32px;cursor:pointer"
            aria-label="Cambiar tema"
        >
            <svg class="tlp-toggle-sun" style="width:16px;height:16px;position:absolute;left:8px;top:8px" viewBox="0 0 24 24"><circle cx="12" cy="12" r="5" fill="currentColor"/><g stroke="currentColor" stroke-width="2.5" stroke-linecap="round" fill="none"><path d="M12 1v3M12 20v3M4.22 4.22l2.12 2.12M17.66 17.66l2.12 2.12M1 12h3M20 12h3M4.22 19.78l2.12-2.12M17.66 6.34l2.12-2.12"/></g></svg>
            <svg class="tlp-toggle-moon" style="width:16px;height:16px;position:absolute;right:8px;top:8px" fill="currentColor" viewBox="0 0 24 24"><path d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            <span class="tlp-toggle-dot" style="display:block;width:24px;height:24px;border-radius:50%;background:#fff;position:absolute;top:4px;left:5px;transition:left 0.2s;box-shadow:0 1px 3px rgba(0,0,0,.2)"></span>
        </button>
        <style>
            .tlp-toggle-btn { background: #e5e7eb; border: 1px solid #d1d5db }
            .tlp-toggle-sun { color: #d97706 }
            .tlp-toggle-moon { color: #9ca3af }
            .dark .tlp-toggle-btn { background: #374151; border-color: #4b5563 }
            .dark .tlp-toggle-sun { color: #fbbf24 }
            .dark .tlp-toggle-moon { color: #e2e8f0 }
        </style>
        <script>
            (function(){
                var b = document.getElementById('theme-toggle-btn');
                if(!b) return;
                var p = b.querySelector('.tlp-toggle-dot');
                if(!p) return;
                var t = localStorage.getItem('theme');
                var isDark = t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches);
                p.style.left = isDark ? '35px' : '5px';
            })();
        </script>
    </div>
</nav>