<script setup>
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import authV2LoginIllustrationBorderedDark from '@images/pages/auth-v2-login-illustration-bordered-dark.png'
import authV2LoginIllustrationBorderedLight from '@images/pages/auth-v2-login-illustration-bordered-light.png'
import authV2LoginIllustrationDark from '@images/pages/auth-v2-login-illustration-dark.png'
import authV2LoginIllustrationLight from '@images/pages/auth-v2-login-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { $api } from '@/utils/api'

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const router = useRouter()
const route = useRoute()
const ability = useAbility()

const form = ref({
  email: '',
  password: '',
  remember: false,
})

const isPasswordVisible = ref(false)
const loginError = ref('')
const isLoading = ref(false)

const login = async () => {
  loginError.value = ''
  isLoading.value = true
  
  try {
    // Haal eerst CSRF cookie op
    await fetch('/sanctum/csrf-cookie', {
      credentials: 'include'
    })
    
    // Dan login
    const response = await $api('/auth/login', {
      method: 'POST',
      body: {
        email: form.value.email,
        password: form.value.password,
      },
    })

    // Store auth data
    const { accessToken, userData, userAbilityRules } = response
    
    useCookie('accessToken').value = accessToken
    useCookie('userData').value = userData
    useCookie('userAbilityRules').value = userAbilityRules
    
    // Update ability rules
    ability.update(userAbilityRules)
    
    // Redirect
    await nextTick(() => {
      router.replace(route.query.to ? String(route.query.to) : '/')
    })
  } catch (error) {
    console.error('Login error:', error)
    loginError.value = 'Email of wachtwoord is onjuist'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <div class="auth-wrapper d-flex align-center justify-center pa-4">
    <VCard class="auth-card pa-4 pt-7" max-width="448">
      <VCardItem class="justify-center">
        <VCardTitle class="font-weight-bold text-h5">
          TC Zutendaal Admin
        </VCardTitle>
      </VCardItem>

      <VCardText class="pt-2">
        <h5 class="text-h5 font-weight-semibold mb-1">
          Welkom! 👋🎾
        </h5>
        <p class="mb-0">
          Log in voor het beheerportaal
        </p>
      </VCardText>

      <VCardText>
        <!-- Tijdelijke info box - verwijder later -->
        <VAlert 
          type="info" 
          variant="tonal"
          class="mb-4"
        >
          <strong>Test credentials:</strong><br>
          Email: admin@tczutendaal.be<br>
          Password: TempPassword123!
        </VAlert>

        <VForm @submit.prevent="login">
          <VRow>
            <VCol cols="12">
              <VTextField
                v-model="form.email"
                label="Email"
                type="email"
                required
                autofocus
              />
            </VCol>

            <VCol cols="12">
              <VTextField
                v-model="form.password"
                label="Wachtwoord"
                :type="isPasswordVisible ? 'text' : 'password'"
                :append-inner-icon="isPasswordVisible ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="isPasswordVisible = !isPasswordVisible"
                required
              />

              <!-- Error message -->
              <VAlert
                v-if="loginError"
                type="error"
                variant="tonal"
                class="mt-2"
              >
                {{ loginError }}
              </VAlert>

              <VBtn 
                block 
                class="mt-4" 
                type="submit"
                :loading="isLoading"
                :disabled="!form.email || !form.password"
              >
                Login
              </VBtn>
            </VCol>
          </VRow>
        </VForm>
      </VCardText>
    </VCard>
  </div>
</template>

<style lang="scss">
.auth-wrapper {
  min-height: 100vh;
}
</style>
