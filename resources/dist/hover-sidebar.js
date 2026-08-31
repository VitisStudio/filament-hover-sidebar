;(() => {
    const BREAKPOINT = 1024
    const PERSIST_KEY = 'fhs.pinned'

    const config = () =>
        window.fhsConfig ?? {
            openDelay: 90,
            closeDelay: 180,
            pinnable: true,
            pinnedByDefault: false,
        }

    const isDesktopHover = () =>
        window.matchMedia(
            `(min-width: ${BREAKPOINT}px) and (hover: hover) and (pointer: fine)`,
        ).matches

    document.addEventListener('alpine:init', () => {
        if (!window.fhsConfig) {
            return
        }

        window.Alpine.store('fhs', {
            pinned: window.Alpine.$persist(config().pinnedByDefault).as(PERSIST_KEY),

            peeking: false,

            timer: null,

            // Core's sidebar reads a single `isOpen` flag for labels, tooltips, aria and
            // group dropdown-vs-inline. Driving it from here keeps all of that consistent
            // without touching a Blade view.
            sync() {
                const sidebar = window.Alpine.store('sidebar')
                const open = this.pinned || this.peeking

                document.body.classList.toggle('fhs-pinned', this.pinned)

                if (isDesktopHover()) {
                    sidebar.isOpen = open
                }
            },

            peek(open) {
                if (this.pinned || !isDesktopHover()) {
                    return
                }

                clearTimeout(this.timer)

                this.timer = setTimeout(
                    () => {
                        this.peeking = open
                        this.sync()
                    },
                    open ? config().openDelay : config().closeDelay,
                )
            },

            togglePin() {
                this.pinned = !this.pinned
                this.peeking = false
                this.sync()
            },
        })
    })

    const bind = () => {
        const sidebar = document.getElementById('fi-main-sidebar')

        if (!sidebar || sidebar.dataset.fhsBound) {
            return
        }

        sidebar.dataset.fhsBound = '1'

        const store = window.Alpine.store('fhs')

        sidebar.addEventListener('mouseenter', () => store.peek(true))
        sidebar.addEventListener('mouseleave', () => store.peek(false))
        sidebar.addEventListener('focusin', () => store.peek(true))
        sidebar.addEventListener('focusout', (event) => {
            if (!sidebar.contains(event.relatedTarget)) {
                store.peek(false)
            }
        })
    }

    // Filament registers `$store.sidebar` inside its own `alpine:init` listener, so reading it
    // is only safe from `alpine:initialized`. Registering `$store.fhs` on `alpine:init` keeps
    // the Blade bindings valid from the first render.
    document.addEventListener('alpine:initialized', () => {
        if (!window.fhsConfig) {
            return
        }

        window.Alpine.store('fhs').sync()
        bind()

        // Livewire SPA navigation can replace the sidebar node.
        document.addEventListener('livewire:navigated', () => {
            window.Alpine.store('fhs').sync()
            bind()
        })

        window
            .matchMedia(`(min-width: ${BREAKPOINT}px)`)
            .addEventListener('change', () => window.Alpine.store('fhs').sync())
    })
})()
