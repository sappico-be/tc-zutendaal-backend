<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Herinnering Instellingen</h1>
            <p class="text-body-1 mt-1" v-if="lessonPackage">{{ lessonPackage.name }}</p>
          </div>
          <VBtn 
            variant="outlined"
            :to="{ name: 'tennis-lessons-packages' }"
          >
            Terug
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Settings Form -->
    <VRow>
      <VCol cols="12" md="8">
        <VCard>
          <VCardItem>
            <VCardTitle>Automatische Herinneringen</VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VSwitch
              v-model="settings.enabled"
              label="Automatische herinneringen inschakelen"
              color="primary"
              class="mb-4"
            />
            
            <div v-if="settings.enabled">
              <VRow>
                <VCol cols="12" md="6">
                  <VSelect
                    v-model="settings.days_before"
                    label="Dagen voor de les"
                    :items="dayOptions"
                    item-title="text"
                    item-value="value"
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="settings.send_time"
                    label="Verzendtijd"
                    type="time"
                  />
                </VCol>
              </VRow>
              
              <div class="mb-4">
                <p class="text-body-1 mb-2">Verzend via:</p>
                <VRadioGroup v-model="settings.channel" inline>
                  <VRadio label="Email" value="email" />
                  <VRadio label="SMS" value="sms" />
                  <VRadio label="Beide" value="both" />
                </VRadioGroup>
              </div>
              
              <VTextarea
                v-if="settings.channel === 'email' || settings.channel === 'both'"
                v-model="settings.email_template"
                label="Email template (optioneel)"
                hint="Laat leeg voor standaard template. Gebruik {naam}, {datum}, {tijd}, {locatie} als placeholders"
                rows="4"
                class="mb-4"
              />
              
              <VTextarea
                v-if="settings.channel === 'sms' || settings.channel === 'both'"
                v-model="settings.sms_template"
                label="SMS template (optioneel)"
                hint="Max 160 karakters. Gebruik {naam}, {datum}, {tijd} als placeholders"
                rows="2"
                counter="160"
                class="mb-4"
              />
            </div>
            
            <VBtn
              color="primary"
              @click="saveSettings"
              :loading="saving"
            >
              Instellingen Opslaan
            </VBtn>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" md="4">
        <VCard>
          <VCardItem>
            <VCardTitle>Test Notificatie</VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VTextField
              v-model="testEmail"
              label="Test email adres"
              type="email"
              class="mb-4"
            />
            
            <VSelect
              v-model="testType"
              label="Type notificatie"
              :items="testTypes"
              class="mb-4"
            />
            
            <VBtn
              block
              color="secondary"
              @click="sendTest"
              :loading="sendingTest"
            >
              Verstuur Test
            </VBtn>
          </VCardText>
        </VCard>
        
        <VCard class="mt-4">
          <VCardItem>
            <VCardTitle>Info</VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VList density="compact">
              <VListItem>
                <template #prepend>
                  <VIcon color="info">tabler-info-circle</VIcon>
                </template>
                <VListItemTitle>Herinneringen worden automatisch verzonden</VListItemTitle>
              </VListItem>
              
              <VListItem>
                <template #prepend>
                  <VIcon color="warning">tabler-clock</VIcon>
                </template>
                <VListItemTitle>Controleer de verzendtijd goed</VListItemTitle>
              </VListItem>
              
              <VListItem>
                <template #prepend>
                  <VIcon color="success">tabler-mail</VIcon>
                </template>
                <VListItemTitle>Test eerst met je eigen email</VListItemTitle>
              </VListItem>
            </VList>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const packageId = route.params.id

const lessonPackage = ref(null)
const settings = ref({
  enabled: true,
  days_before: 1,
  send_time: '19:00',
  channel: 'email',
  email_template: '',
  sms_template: '',
})

const saving = ref(false)
const sendingTest = ref(false)
const testEmail = ref('')
const testType = ref('reminder')

const dayOptions = [
  { value: 0, text: 'Op de dag zelf' },
  { value: 1, text: '1 dag van tevoren' },
  { value: 2, text: '2 dagen van tevoren' },
  { value: 3, text: '3 dagen van tevoren' },
  { value: 7, text: '1 week van tevoren' },
]

const testTypes = [
  { title: 'Herinnering', value: 'reminder' },
  { title: 'Annulering', value: 'cancelled' },
  { title: 'Wijziging', value: 'changed' },
]

const loadSettings = async () => {
  try {
    // Load package
    const packageResponse = await axios.get(`/lessons/packages/${packageId}`)
    lessonPackage.value = packageResponse.data.data
    
    // Load settings
    const settingsResponse = await axios.get(`/lessons/packages/${packageId}/reminder-settings`)
    if (settingsResponse.data.data) {
      settings.value = settingsResponse.data.data
    }
  } catch (error) {
    console.error('Error loading settings:', error)
  }
}

const saveSettings = async () => {
  saving.value = true
  
  try {
    await axios.post(`/lessons/packages/${packageId}/reminder-settings`, settings.value)
    alert('Instellingen opgeslagen')
  } catch (error) {
    console.error('Error saving settings:', error)
    alert('Fout bij opslaan instellingen')
  } finally {
    saving.value = false
  }
}

const sendTest = async () => {
  if (!testEmail.value) {
    alert('Vul een email adres in')
    return
  }
  
  sendingTest.value = true
  
  try {
    const response = await axios.post(`/lessons/packages/${packageId}/test-notification`, {
      email: testEmail.value,
      type: testType.value
    })
    
    alert(response.data.message || 'Test notificatie verzonden!')
  } catch (error) {
    console.error('Error sending test:', error)
    alert(error.response?.data?.error || 'Fout bij verzenden test')
  } finally {
    sendingTest.value = false
  }
}

onMounted(() => {
  loadSettings()
  testEmail.value = '{{ auth()->user()->email }}'
})
</script>
