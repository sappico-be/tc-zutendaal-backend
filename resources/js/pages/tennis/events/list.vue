<template>
  <VContainer>
    <!-- Page Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <h1 class="text-h4">Evenementen Beheer</h1>
          <VBtn 
            color="primary" 
            prepend-icon="tabler-plus"
            :to="{ name: 'tennis-events-create' }"
          >
            Nieuw Event
          </VBtn>
        </div>
      </VCol>
    </VRow>

    <!-- Events Table -->
    <VRow>
      <VCol cols="12">
        <VCard>
          <VCardText>
            <VDataTable
              :headers="headers"
              :items="events"
              :loading="loading"
              :items-per-page="10"
            >
              <template #item.type="{ item }">
                <VChip
                  :color="getTypeColor(item.type)"
                  size="small"
                >
                  {{ item.type }}
                </VChip>
              </template>
              
              <template #item.start_date="{ item }">
                {{ formatDate(item.start_date) }}
              </template>
              
              <template #item.registrations="{ item }">
                <VChip size="small" variant="tonal">
                  {{ item.confirmed_registrations_count || 0 }} / {{ item.max_participants || '∞' }}
                </VChip>
              </template>
              
              <template #item.status="{ item }">
                <VChip
                  :color="getStatusColor(item.status)"
                  size="small"
                >
                  {{ item.status }}
                </VChip>
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  icon="tabler-users"
                  size="small"
                  variant="text"
                  color="info"
                  :to="{ name: 'tennis-event-registrations', params: { id: item.id } }"
                />
                <VBtn
                  icon="tabler-edit"
                  size="small"
                  variant="text"
                  :to="{ name: 'tennis-events-edit', params: { id: item.id } }"
                />
                <VBtn
                  icon="tabler-trash"
                  size="small"
                  variant="text"
                  color="error"
                  @click="deleteEvent(item)"
                />
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const loading = ref(false)
const events = ref([])

const headers = [
  { title: 'Titel', key: 'title' },
  { title: 'Type', key: 'type' },
  { title: 'Datum', key: 'start_date' },
  { title: 'Locatie', key: 'location' },
  { title: 'Inschrijvingen', key: 'registrations' },
  { title: 'Status', key: 'status' },
  { title: 'Acties', key: 'actions', sortable: false },
]

const loadEvents = async () => {
  loading.value = true
  try {
    const response = await axios.get('/events')
    events.value = response.data.data
  } catch (error) {
    console.error('Error loading events:', error)
  } finally {
    loading.value = false
  }
}

const getTypeColor = (type) => {
  const colors = {
    tournament: 'primary',
    training: 'success',
    social: 'warning',
    meeting: 'info',
    other: 'secondary',
  }
  return colors[type] || 'default'
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'warning',
    published: 'success',
    cancelled: 'error',
    completed: 'secondary',
  }
  return colors[status] || 'default'
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const deleteEvent = async (item) => {
  if (confirm(`Weet je zeker dat je "${item.title}" wilt verwijderen?`)) {
    try {
      await axios.delete(`/events/${item.id}`)
      await loadEvents()
    } catch (error) {
      console.error('Error deleting event:', error)
      alert('Kan event niet verwijderen - er zijn mogelijk al inschrijvingen')
    }
  }
}

onMounted(() => {
  loadEvents()
})
</script>
