import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const IDLE_TIMEOUT_MS = 3 * 60 * 1000 // 3 menit
const WARN_BEFORE_MS  = 30 * 1000      // warning muncul 30 detik sebelum logout

// Events yang dianggap sebagai aktivitas user
const ACTIVITY_EVENTS = [
  'mousemove', 'mousedown', 'keydown',
  'touchstart', 'touchmove', 'scroll', 'wheel', 'click',
]

export function useIdleLogout() {
  const router    = useRouter()
  const auth      = useAuthStore()
  const showWarn  = ref(false)       // tampilkan modal warning countdown
  const countdown = ref(30)          // detik sebelum logout

  let idleTimer:    ReturnType<typeof setTimeout>  | null = null
  let warnTimer:    ReturnType<typeof setTimeout>  | null = null
  let countdownInt: ReturnType<typeof setInterval> | null = null

  function resetTimers() {
    if (!auth.isAuthenticated) return

    // Sembunyikan warning kalau masih muncul
    if (showWarn.value) {
      showWarn.value = false
      countdown.value = 30
      clearInterval(countdownInt!)
    }

    clearTimeout(idleTimer!)
    clearTimeout(warnTimer!)

    // Timer warning: 2 menit 30 detik
    warnTimer = setTimeout(() => {
      showWarn.value  = true
      countdown.value = 30

      countdownInt = setInterval(() => {
        countdown.value--
        if (countdown.value <= 0) clearInterval(countdownInt!)
      }, 1000)
    }, IDLE_TIMEOUT_MS - WARN_BEFORE_MS)

    // Timer logout: 3 menit
    idleTimer = setTimeout(async () => {
      clearInterval(countdownInt!)
      showWarn.value = false
      await auth.logout()
      router.push('/login')
    }, IDLE_TIMEOUT_MS)
  }

  function stayActive() {
    resetTimers()
  }

  onMounted(() => {
    if (!auth.isAuthenticated) return
    ACTIVITY_EVENTS.forEach(e => window.addEventListener(e, resetTimers, { passive: true }))
    resetTimers()
  })

  onUnmounted(() => {
    ACTIVITY_EVENTS.forEach(e => window.removeEventListener(e, resetTimers))
    clearTimeout(idleTimer!)
    clearTimeout(warnTimer!)
    clearInterval(countdownInt!)
  })

  return { showWarn, countdown, stayActive }
}
