<template>
  <VContainer>
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>
              {{ isEdit ? 'Evenement Bewerken' : 'Nieuw Evenement' }}
            </VCardTitle>
          </VCardItem>
          
          <VCardText>
            <VForm @submit.prevent="saveEvent">
              <VRow>
                <VCol cols="12" md="8">
                  <VTextField
                    v-model="event.title"
                    label="Titel"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VSelect
                    v-model="event.type"
                    label="Type"
                    :items="typeOptions"
                  />
                </VCol>
                
                <VCol cols="12">
                  <VTextarea
                    v-model="event.description"
                    label="Beschrijving"
                    rows="3"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VTextField
                    v-model="event.location"
                    label="Locatie"
                  />
                </VCol>
                
                <VCol cols="12" md="6">
                  <VSelect
                    v-model="event.status"
                    label="Status"
                    :items="statusOptions"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="event.start_date"
                    label="Start datum"
                    type="datetime-local"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="event.end_date"
                    label="Eind datum"
                    type="datetime-local"
                    required
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model="event.registration_deadline"
                    label="Inschrijf deadline"
                    type="datetime-local"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="event.max_participants"
                    label="Max deelnemers"
                    type="number"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="event.price_members"
                    label="Prijs leden (€)"
                    type="number"
                    step="0.01"
                  />
                </VCol>
                
                <VCol cols="12" md="4">
                  <VTextField
                    v-model.number="event.price_non_members"
                    label="Prijs niet-leden (€)"
                    type="number"
                    step="0.01"
                  />
                </VCol>
                
                <VCol cols="12">
                  <VCheckbox
                    v-model="event.members_only"
                    label="Alleen voor leden"
                  />
                </VCol>
                
                <VCol cols="12">
                  <VBtn
                    type="submit"
                    color="primary"
                    class="mr-3"
                    :loading="loading"
                  >
                    Opslaan
                  </VBtn>
                  <VBtn
                    variant="outlined"
                    :to="{ name: 'tennis-events-list' }"
                  >
                    Annuleren
                  </VBtn>
                </VCol>
              </VRow>
            </VForm>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const router = useRouter()
const isEdit = ref(false)
const loading = ref(false)

const event = ref({
  title: '',
  description: '',
  type: 'other',
  location: '',
  status: 'draft',
  start_date: '',
  end_date: '',
  registration_deadline: '',
  max_participants: null,
  price_members: 0,
  price_non_members: 0,
  members_only: false,
})

const typeOptions = [
  { title: 'Toernooi', value: 'tournament' },
  { title: 'Training', value: 'training' },
  { title: 'Sociaal', value: 'social' },
  { title: 'Vergadering', value: 'meeting' },
  { title: 'Anders', value: 'other' },
]

const statusOptions = [
  { title: 'Concept', value: 'draft' },
  { title: 'Gepubliceerd', value: 'published' },
  { title: 'Geannuleerd', value: 'cancelled' },
]

const saveEvent = async () => {
  loading.value = true
  try {
    // Format dates voor de API
    const eventData = {
      ...event.value,
      start_date: event.value.start_date ? new Date(event.value.start_date).toISOString() : null,
      end_date: event.value.end_date ? new Date(event.value.end_date).toISOString() : null,
      registration_deadline: event.value.registration_deadline ? new Date(event.value.registration_deadline).toISOString() : null,
    }
    
    if (isEdit.value) {
      await axios.put(`/events/${route.params.id}`, eventData)
    } else {
      await axios.post('/events', eventData)
    }
    router.push({ name: 'tennis-events-list' })
  } catch (error) {
    console.error('Error saving event:', error)
    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat().join('\n')
      alert(`Validatie errors:\n${errors}`)
    } else {
      alert('Error bij het opslaan. Check alle velden.')
    }
  } finally {
    loading.value = false
  }
}

const loadEvent = async () => {
  if (route.params.id) {
    isEdit.value = true
    try {
      const response = await axios.get(`/events/${route.params.id}`)
      event.value = response.data.data
      // Format dates for datetime-local input
      if (event.value.start_date) {
        event.value.start_date = formatDateForInput(event.value.start_date)
      }
      if (event.value.end_date) {
        event.value.end_date = formatDateForInput(event.value.end_date)
      }
      if (event.value.registration_deadline) {
        event.value.registration_deadline = formatDateForInput(event.value.registration_deadline)
      }
    } catch (error) {
      console.error('Error loading event:', error)
    }
  }
}

const formatDateForInput = (date) => {
  if (!date) return ''
  return new Date(date).toISOString().slice(0, 16)
}

onMounted(() => {
  loadEvent()
})
</script>
