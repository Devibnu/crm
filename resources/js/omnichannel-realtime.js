import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

const workspace = document.querySelector('[data-omni-workspace]')
const bridge = window.krakatauOmnichannelRealtime

if (workspace && bridge) {
  const fallback = () => bridge.setStatus?.('fallback')
  const enabled = workspace.dataset.reverbEnabled === 'true'
  const key = workspace.dataset.reverbKey || import.meta.env.VITE_REVERB_APP_KEY

  if (!enabled || !key) {
    fallback()
  } else {
    window.Pusher = Pusher

    const host = workspace.dataset.reverbHost || import.meta.env.VITE_REVERB_HOST || window.location.hostname
    const port = Number(workspace.dataset.reverbPort || import.meta.env.VITE_REVERB_PORT || 8080)
    const scheme = workspace.dataset.reverbScheme || import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || ''
    const forceTLS = scheme === 'https'
    const events = [
      'ConversationUpdated',
      'MessageReceived',
      'MessageSent',
      'ConversationAssigned',
      'ConversationResolved',
      'ConversationNoteCreated',
    ]

    let echo = null
    let refreshTimer = null
    let activeConversationChannel = null

    const scheduleRefresh = event => {
      window.clearTimeout(refreshTimer)
      refreshTimer = window.setTimeout(() => {
        bridge.refresh?.(event)
      }, 150)
    }

    const listenTo = channel => {
      events.forEach(eventName => {
        channel.listen(`.${eventName}`, payload => {
          bridge.setStatus?.('connected')
          scheduleRefresh({ ...payload, type: eventName })
        })
      })
    }

    const subscribeActiveConversation = () => {
      if (!echo) return

      const conversationId = String(bridge.activeConversationId?.() || '')
      if (!conversationId || conversationId === activeConversationChannel) return

      if (activeConversationChannel) {
        echo.leave(`omnichannel.conversation.${activeConversationChannel}`)
      }

      activeConversationChannel = conversationId
      listenTo(echo.private(`omnichannel.conversation.${conversationId}`))
    }

    try {
      echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        auth: {
          headers: {
            'X-CSRF-TOKEN': csrfToken,
          },
        },
      })

      const connection = echo.connector?.pusher?.connection

      connection?.bind('connected', () => {
        bridge.setStatus?.('connected')
        subscribeActiveConversation()
      })
      connection?.bind('connecting', () => bridge.setStatus?.('reconnecting'))
      connection?.bind('unavailable', fallback)
      connection?.bind('failed', fallback)
      connection?.bind('error', () => bridge.setStatus?.('reconnecting'))
      connection?.bind('disconnected', fallback)

      listenTo(echo.private('omnichannel'))
      subscribeActiveConversation()
      window.setInterval(subscribeActiveConversation, 1000)
      window.addEventListener('beforeunload', () => echo?.disconnect())
    } catch (error) {
      console.error('Failed to initialize Omnichannel Reverb:', error)
      fallback()
    }
  }
}
