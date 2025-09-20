<template>
  <VDialog v-model="show" max-width="600" persistent>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span>Notificatie Instellingen</span>
        <VBtn icon="tabler-x" variant="text" @click="close" />
      </VCardTitle>
      
      <VCardText>
        <!-- Group Info -->
        <VAlert type="info" class="mb-4" v-if="group">
          <div><strong>Groep:</strong> {{ group.name }}</div>
          <div><strong>Trainer:</strong> {{ group.trainer?.name || 'Niet toegewezen' }}</div>
          <div><strong>Leden:</strong> {{ group.registrations?.length || 0 }}</div>
        </VAlert>

        <!-- Notification Type -->
        <VSelect
          v-model="notification.type"
          label="Type Notificatie"
          :items="notificationTypes"
          class="mb-4"
        />

        <!-- Schedule Reminder (for lesson_reminder type) -->
        <div v-if="notification.type === 'lesson_reminder'">
          <VSelect
            v-model="selectedLesson"
            label="Selecteer Les"
            :items="upcomingLessons"
            item-title="display"
            item-value="id"
            class="mb-4"
          />
          
          <VTextField
            v-model.number="notification.hours_before"
            label="Uren voor de les"
            type="number"
            min="1"
            max="72"
            hint="Hoeveel uur van tevoren moet de herinnering verstuurd worden?"
            persistent-hint
            class="mb-4"
          />
        </div>

        <!-- Lesson Selection for cancelled/changed notifications -->
        <div v-else-if="notification.type === 'lesson_cancelled' || notification.type === 'schedule_change'">
          <VSelect
            v-model="selectedLesson"
            label="Selecteer de les"
            :items="upcomingLessons"
            item-title="display"
            item-value="id"
            class="mb-4"
            :hint="notification.type === 'lesson_cancelled' ? 'Welke les wordt geannuleerd?' : 'Welke les wordt gewijzigd?'"
            persistent-hint
          />
          
          <VTextField
            v-model="notification.subject"
            label="Onderwerp"
            class="mb-4"
          />
          
          <VTextarea
            v-model="notification.message"
            label="Bericht"
            rows="5"
            hint="De placeholders {name}, {group}, {date}, {time} en {location} worden automatisch vervangen"
            persistent-hint
            class="mb-4"
          />
        </div>

        <!-- Custom Message -->
        <div v-else-if="notification.type === 'custom'">
          <VTextField
            v-model="notification.subject"
            label="Onderwerp"
            class="mb-4"
          />
          
          <VTextarea
            v-model="notification.message"
            label="Bericht"
            rows="5"
            hint="Gebruik {name} voor de naam van het lid, {group} voor groepsnaam, {date} voor datum, {time} voor tijd"
            persistent-hint
            class="mb-4"
          />
        </div>

        <!-- Recipients -->
        <VCheckbox
          v-model="notification.send_email"
          label="Verstuur via Email"
          class="mb-2"
        />
        
        <VCheckbox
          v-model="notification.send_sms"
          label="Verstuur via SMS (indien beschikbaar)"
          disabled
          hint="SMS functionaliteit komt binnenkort"
          persistent-hint
        />

        <!-- Send Options -->
        <VRadioGroup
          v-model="sendOption"
          class="mt-4"
        >
          <VRadio
            label="Verstuur nu"
            value="now"
          />
          <VRadio
            label="Plan voor later"
            value="scheduled"
          />
        </VRadioGroup>

        <!-- Schedule DateTime (if scheduled) -->
        <div v-if="sendOption === 'scheduled'" class="mt-4">
          <VRow>
            <VCol cols="6">
              <VTextField
                v-model="scheduledDate"
                label="Datum"
                type="date"
                :min="minDate"
              />
            </VCol>
            <VCol cols="6">
              <VTextField
                v-model="scheduledTime"
                label="Tijd"
                type="time"
              />
            </VCol>
          </VRow>
        </div>

        <!-- Test Mode -->
        <VAlert type="warning" class="mt-4">
          <VCheckbox
            v-model="testMode"
            label="Test modus (stuur alleen naar jezelf)"
            hide-details
          />
        </VAlert>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn variant="outlined" @click="close">Annuleren</VBtn>
        <VBtn 
          color="primary" 
          @click="sendNotification"
          :loading="sending"
        >
          {{ sendOption === 'now' ? 'Verstuur' : 'Plan Notificatie' }}
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from '@/plugins/axios'

const props = defineProps({
  modelValue: Boolean,
  group: Object,
  packageId: String,
})

const emit = defineEmits(['update:modelValue', 'sent'])

const show = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const sending = ref(false)
const testMode = ref(true) // Default to test mode for safety
const sendOption = ref('now')
const scheduledDate = ref('')
const scheduledTime = ref('')
const selectedLesson = ref(null)
const upcomingLessons = ref([])

const notification = ref({
  type: 'custom',
  subject: '',
  message: '',
  send_email: true,
  send_sms: false,
  hours_before: 24,
})

const notificationTypes = [
  { title: 'Aangepast Bericht', value: 'custom' },
  { title: 'Les Herinnering', value: 'lesson_reminder' },
  { title: 'Les Geannuleerd', value: 'lesson_cancelled' },
  { title: 'Rooster Wijziging', value: 'schedule_change' },
]

const minDate = computed(() => {
  const today = new Date()
  return today.toISOString().split('T')[0]
})

const loadUpcomingLessons = async () => {
  if (!props.group?.id) {
    console.log('No group ID provided')
    return
  }
  
  console.log('Loading lessons for group:', props.group.id, 'Package:', props.packageId)
  
  try {
    const response = await axios.get(
      `/lessons/packages/${props.packageId}/groups/${props.group.id}/schedule`
    )
    
    console.log('API Response:', response.data)
    
    // Check if we have data
    if (!response.data.data || response.data.data.length === 0) {
      console.log('No lessons found for this group')
      // Show all lessons regardless of status for now
      upcomingLessons.value = []
      return
    }
    
    // Filter only upcoming lessons
    const now = new Date()
    const allLessons = response.data.data
    console.log('All lessons:', allLessons)
    
    // First try: get all scheduled lessons (upcoming and past for debugging)
    const scheduledLessons = allLessons
      .filter(lesson => lesson.status === 'scheduled')
      .map(lesson => ({
        id: lesson.id,
        display: `${formatDate(lesson.lesson_date)} - ${lesson.start_time} (${lesson.status})`,
        ...lesson
      }))
    
    console.log('Scheduled lessons:', scheduledLessons)
    
    // If no scheduled lessons, show all lessons for debugging
    if (scheduledLessons.length === 0) {
      console.log('No scheduled lessons, showing all lessons')
      upcomingLessons.value = allLessons.map(lesson => ({
        id: lesson.id,
        display: `${formatDate(lesson.lesson_date)} - ${lesson.start_time} (${lesson.status})`,
        ...lesson
      }))
    } else {
      // Filter for upcoming only
      const upcoming = scheduledLessons.filter(lesson => {
        const lessonDate = new Date(`${lesson.lesson_date} ${lesson.start_time}`)
        return lessonDate > now
      })
      
      // If no upcoming, show all scheduled (including past)
      upcomingLessons.value = upcoming.length > 0 ? upcoming : scheduledLessons
    }
    
    console.log('Final upcoming lessons:', upcomingLessons.value)
  } catch (error) {
    console.error('Error loading lessons:', error)
    console.error('Error details:', error.response?.data)
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  const days = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za']
  return `${days[d.getDay()]} ${d.toLocaleDateString('nl-BE')}`
}

const sendNotification = async () => {
  if (!props.group?.id) {
    alert('Geen groep geselecteerd')
    return
  }
  
  // Validation
  if (notification.value.type === 'custom') {
    if (!notification.value.subject || !notification.value.message) {
      alert('Vul een onderwerp en bericht in')
      return
    }
  } else if (notification.value.type === 'lesson_reminder') {
    if (!selectedLesson.value) {
      alert('Selecteer een les')
      return
    }
  } else if (notification.value.type === 'lesson_cancelled' || notification.value.type === 'schedule_change') {
    if (!selectedLesson.value) {
      alert('Selecteer welke les het betreft')
      return
    }
  }
  
  if (sendOption.value === 'scheduled' && (!scheduledDate.value || !scheduledTime.value)) {
    alert('Selecteer datum en tijd voor geplande notificatie')
    return
  }
  
  sending.value = true
  
  try {
    const payload = {
      group_id: props.group.id,
      type: notification.value.type,
      subject: notification.value.subject,
      message: notification.value.message,
      send_email: notification.value.send_email,
      send_sms: notification.value.send_sms,
      test_mode: testMode.value,
    }
    
    // Voor alle types behalve custom, voeg lesson_schedule_id toe
    if (notification.value.type !== 'custom' && selectedLesson.value) {
      payload.lesson_schedule_id = selectedLesson.value
    }
    
    if (notification.value.type === 'lesson_reminder') {
      payload.hours_before = notification.value.hours_before
    }
    
    if (sendOption.value === 'scheduled') {
      payload.scheduled_at = `${scheduledDate.value} ${scheduledTime.value}`
    }
    
    const response = await axios.post(
      `/lessons/packages/${props.packageId}/notifications`,
      payload
    )
    
    alert(response.data.message || 'Notificatie verstuurd!')
    emit('sent')
    close()
  } catch (error) {
    console.error('Error sending notification:', error)
    alert('Fout bij versturen notificatie: ' + (error.response?.data?.message || 'Onbekende fout'))
  } finally {
    sending.value = false
  }
}

const close = () => {
  show.value = false
  resetForm()
}

const resetForm = () => {
  notification.value = {
    type: 'custom',
    subject: '',
    message: '',
    send_email: true,
    send_sms: false,
    hours_before: 24,
  }
  sendOption.value = 'now'
  scheduledDate.value = ''
  scheduledTime.value = ''
  selectedLesson.value = null
  testMode.value = true
}

// Set default templates based on type
watch(() => notification.value.type, (newType) => {
  // Reset selected lesson when changing type
  if (newType !== 'lesson_reminder' && newType !== 'lesson_cancelled' && newType !== 'schedule_change') {
    selectedLesson.value = null
  }
  
  switch(newType) {
    case 'lesson_reminder':
      notification.value.subject = 'Herinnering: Tennis les {date}'
      notification.value.message = `Beste {name},

Dit is een herinnering voor je tennisles morgen.

Groep: {group}
Datum: {date}
Tijd: {time}
Locatie: {location}

Tot morgen!

Met sportieve groeten,
TC Zutendaal`
      break
      
    case 'lesson_cancelled':
      notification.value.subject = 'GEANNULEERD: Les {date}'
      notification.value.message = `Beste {name},

Helaas moet de les van {date} om {time} worden geannuleerd.

Groep: {group}
Locatie: {location}

We laten je zo snel mogelijk weten wanneer de vervangende les zal plaatsvinden.

Met excuses voor het ongemak.

Met sportieve groeten,
TC Zutendaal`
      break
      
    case 'schedule_change':
      notification.value.subject = 'WIJZIGING: Les {date}'
      notification.value.message = `Beste {name},

Er is een wijziging voor de les van {date}.

Groep: {group}
Nieuwe tijd: {time}
Nieuwe locatie: {location}

[Beschrijf hier de specifieke wijziging]

Noteer deze wijziging in je agenda!

Met sportieve groeten,
TC Zutendaal`
      break
      
    default:
      notification.value.subject = ''
      notification.value.message = ''
  }
})

watch(() => props.modelValue, (newVal) => {
  if (newVal && props.group) {
    loadUpcomingLessons()
  }
})

onMounted(() => {
  if (props.modelValue && props.group) {
    loadUpcomingLessons()
  }
})
</script>
