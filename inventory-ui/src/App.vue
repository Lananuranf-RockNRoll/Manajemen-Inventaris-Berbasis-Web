<template>
  <RouterView/>
</template>

<script lang="ts" setup>
import {onMounted, onUnmounted} from 'vue'
import {useAuthStore} from '@/stores/auth'
import {useRouter} from 'vue-router'

const auth = useAuthStore()
const router = useRouter()
let idleTimer: ReturnType<typeof setTimeout>

function resetTimer() {
  clearTimeout(idleTimer)
  idleTimer = setTimeout(() => {
    if (auth.isAuthenticated) {
      auth.logout()
      router.push('/login')
    }
  }, 3 * 60 * 1000) // 3 menit
}

onMounted(() => {
  window.addEventListener('mousemove', resetTimer)
  window.addEventListener('keydown', resetTimer)
  window.addEventListener('click', resetTimer)
  window.addEventListener('scroll', resetTimer)
  resetTimer()
})

onUnmounted(() => {
  window.removeEventListener('mousemove', resetTimer)
  window.removeEventListener('keydown', resetTimer)
  window.removeEventListener('click', resetTimer)
  window.removeEventListener('scroll', resetTimer)
  clearTimeout(idleTimer)
})
</script>
