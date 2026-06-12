<div
    wire:poll.15s="updateBadge"
    x-data="{
        badgeContent: @entangle('badgeContent'),
        shouldShow: @entangle('shouldShow'),
        url: @entangle('webmailUrl'),
        init() {
            this.updateDom();
            this.$watch('badgeContent', () => this.updateDom());
            this.$watch('shouldShow', () => this.updateDom());
            this.$watch('url', () => this.updateDom());
            document.addEventListener('livewire:navigated', () => this.updateDom());
            document.addEventListener('visibilitychange', () => this.updateDom());
        },
        updateDom() {
            const items = Array.from(document.querySelectorAll('.fi-sidebar a'))
            let link = null

            if (this.url && this.url !== '#') {
                link = document.querySelector(`.fi-sidebar a[href='${this.url}']`)
            }

            if (!link) {
                link = items.find(el => {
                    const text = (el.textContent || '').trim()
                    return text.includes('Mis Correos')
                })
            }

            if (!link) return

            let badge = link.querySelector('.fi-sidebar-item-badge')
                || link.querySelector('.fi-badge')
                || link.querySelector('#fp-webmail-badge')
            if (this.shouldShow && this.badgeContent) {
                if (!badge) {
                    const newBadge = document.createElement('span')
                    newBadge.className = 'fi-sidebar-item-badge flex items-center justify-center gap-x-1 rounded-md text-xs font-medium ring-1 ring-inset px-2 min-w-[theme(spacing.6)] py-1 fi-color-danger bg-danger-50 text-danger-600 ring-danger-600/10 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/30'
                    newBadge.style.color = 'rgb(220 38 38)'
                    newBadge.style.backgroundColor = 'rgb(254 242 242)'
                    newBadge.id = 'fp-webmail-badge'
                    link.appendChild(newBadge)
                    badge = newBadge
                } else {
                    badge.style.display = ''
                }
                if (badge.innerText !== this.badgeContent) {
                    badge.innerText = this.badgeContent
                }
            } else {
                if (badge) {
                    badge.style.display = 'none'
                }
            }

            if (this.shouldShow && this.url && this.url !== '#') {
                link.setAttribute('href', this.url)
                link.setAttribute('target', '_blank')
            }

            if (window.Livewire && typeof window.Livewire.dispatch === 'function') {
                window.Livewire.dispatch('refresh-sidebar')
            }
        }
    }"
>
    @if ($shouldShow)
        <x-filament::icon-button
            :badge="$badgeContent"
            badge-color="danger"
            color="gray"
            icon="heroicon-o-envelope"
            size="lg"
            label="Mis Correos"
            class="fi-topbar-webmail-btn"
            tag="a"
            :href="$webmailUrl"
            target="_blank"
        />
    @endif
</div>
