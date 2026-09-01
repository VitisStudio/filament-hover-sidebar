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

    const isDesktopLayout = () =>
        window.matchMedia(`(min-width: ${BREAKPOINT}px)`).matches

    document.addEventListener('alpine:init', () => {
        if (!window.fhsConfig) {
            return
        }

        window.Alpine.store('fhs', {
            pinned: window.Alpine.$persist(config().pinnedByDefault).as(PERSIST_KEY),

            peeking: false,

            // Whether the open peek came from a tap. A mouse peek closes itself on
            // `pointerleave`; a tapped one has to be dismissed deliberately.
            tapped: false,

            timer: null,

            // Core's sidebar reads a single `isOpen` flag for labels, tooltips, aria and
            // group dropdown-vs-inline. Driving it from here keeps all of that consistent
            // without touching a Blade view.
            sync() {
                const sidebar = window.Alpine.store('sidebar')
                const open = this.pinned || this.peeking

                document.body.classList.toggle('fhs-pinned', this.pinned)

                if (isDesktopLayout()) {
                    sidebar.isOpen = open
                }
            },

            peek(open) {
                if (this.pinned || !isDesktopLayout()) {
                    return
                }

                clearTimeout(this.timer)

                this.timer = setTimeout(
                    () => this.setPeek(open),
                    open ? config().openDelay : config().closeDelay,
                )
            },

            // A press is a deliberate act, so it skips the anti-twitch delays a drifting
            // pointer needs.
            peekNow(open, tapped = false) {
                if (this.pinned || !isDesktopLayout()) {
                    return
                }

                clearTimeout(this.timer)

                this.setPeek(open, tapped)
            },

            setPeek(open, tapped = false) {
                this.peeking = open
                this.tapped = open && tapped
                this.sync()
            },

            togglePin() {
                this.pinned = !this.pinned
                this.peeking = false
                this.tapped = false
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

        // Pointer events rather than mouse events so the touch branch can be told apart:
        // a coarse pointer fires `pointerenter` on tap and never fires `pointerleave`,
        // which on its own would leave the rail stuck open.
        sidebar.addEventListener('pointerenter', (event) => {
            if (event.pointerType === 'mouse') {
                store.peek(true)
            }
        })

        sidebar.addEventListener('pointerleave', (event) => {
            if (event.pointerType === 'mouse') {
                store.peek(false)
            }
        })

        let lastPointerType = 'mouse'

        sidebar.addEventListener('pointerdown', (event) => {
            lastPointerType = event.pointerType || 'mouse'
        })

        // A closed rail shows icons with no labels, so a press on one is a request to see
        // the nav, not to go where that icon leads: it expands the rail and goes nowhere.
        // Once open, the same press navigates.
        //
        // Capture phase, because Livewire's `navigate` binds click on the link itself —
        // `stopPropagation()` here runs first and keeps the event off it. `preventDefault()`
        // alone would not do: a `pointerdown` default that is prevented still produces a
        // click, which is what let a tap through in the first place.
        //
        // `detail === 0` marks a keyboard activation. Those are left alone: `focusin`
        // already expands the rail, and swallowing Enter would strand keyboard users.
        sidebar.addEventListener(
            'click',
            (event) => {
                if (store.pinned || store.peeking || !isDesktopLayout()) {
                    return
                }

                if (event.detail === 0) {
                    return
                }

                event.preventDefault()
                event.stopPropagation()

                store.peekNow(true, lastPointerType !== 'mouse')
            },
            true,
        )

        sidebar.addEventListener('focusin', () => store.peek(true))
        sidebar.addEventListener('focusout', (event) => {
            if (!sidebar.contains(event.relatedTarget)) {
                store.peek(false)
            }
        })
    }

    // Nothing collapses a touch-opened flyout on its own — there is no `pointerleave`,
    // and a tap that lands on the page is how a person dismisses an overlay.
    const bindDismiss = () => {
        document.addEventListener('pointerdown', (event) => {
            if (event.pointerType === 'mouse' || !window.Alpine.store('fhs')?.tapped) {
                return
            }

            if (document.getElementById('fi-main-sidebar')?.contains(event.target)) {
                return
            }

            window.Alpine.store('fhs').peekNow(false)
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
        bindDismiss()

        // Livewire SPA navigation can replace the sidebar node. A tap-opened flyout also
        // has to close behind the page it just opened — a hovered one is left alone, since
        // the pointer may still be resting on the rail.
        document.addEventListener('livewire:navigated', () => {
            const store = window.Alpine.store('fhs')

            if (store.tapped) {
                store.peekNow(false)
            }

            store.sync()
            bind()
        })

        window
            .matchMedia(`(min-width: ${BREAKPOINT}px)`)
            .addEventListener('change', () => window.Alpine.store('fhs').sync())
    })
})()
