<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Uren Registratie</h1>
            <p class="text-body-1 mt-1">
              {{ currentMonth }}
            </p>
          </div>
          <div>
            <VBtn
              color="primary"
              prepend-icon="tabler-plus"
              @click="showAddDialog = true"
              class="mr-2"
            >
              Nieuwe Registratie
            </VBtn>
            <VBtn
              variant="outlined"
              prepend-icon="tabler-download"
              @click="exportHours"
            >
              Export
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>

    <!-- Statistics Cards -->
    <VRow>
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Totaal Uren</div>
                <div class="text-h4 mt-1">{{ totalHours }}</div>
              </div>
              <VIcon size="40" color="primary" icon="tabler-clock" />
            </div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Goedgekeurd</div>
                <div class="text-h4 mt-1 text-success">€{{ approvedAmount }}</div>
              </div>
              <VIcon size="40" color="success" icon="tabler-check" />
            </div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">In Afwachting</div>
                <div class="text-h4 mt-1 text-warning">€{{ pendingAmount }}</div>
              </div>
              <VIcon size="40" color="warning" icon="tabler-clock-pause" />
            </div>
          </VCardText>
        </VCard>
      </VCol>
      
      <VCol cols="12" sm="6" md="3">
        <VCard>
          <VCardText>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-body-2 text-disabled">Status</div>
                <div class="text-h5 mt-1">
                  <VChip :color="getStatusColor(monthlyStatus)" size="small">
                    {{ monthlyStatus }}
                  </VChip>
                </div>
              </div>
              <VIcon size="40" color="info" icon="tabler-file-text" />
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Filters -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardText>
            <VRow>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="selectedMonth"
                  label="Maand"
                  :items="monthOptions"
                  density="compact"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="selectedYear"
                  label="Jaar"
                  :items="yearOptions"
                  density="compact"
                />
              </VCol>
              <VCol cols="12" md="3" v-if="isAdmin">
                <VSelect
                  v-model="selectedTrainer"
                  label="Trainer"
                  :items="trainers"
                  item-title="name"
                  item-value="id"
                  clearable
                  density="compact"
                />
              </VCol>
              <VCol cols="12" md="3">
                <VSelect
                  v-model="filterStatus"
                  label="Status"
                  :items="statusOptions"
                  clearable
                  density="compact"
                />
              </VCol>
            </VRow>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Hours Table -->
    <VRow class="mt-6">
      <VCol cols="12">
        <VCard>
          <VCardItem>
            <VCardTitle>Geregistreerde Uren</VCardTitle>
            <template #append v-if="isAdmin && pendingRegistrations.length > 0">
              <VBtn
                size="small"
                color="success"
                @click="approveAll"
                prepend-icon="tabler-check"
              >
                Keur Alles Goed ({{ pendingRegistrations.length }})
              </VBtn>
            </template>
          </VCardItem>
          
          <VCardText>
            <VDataTable
              :headers="headers"
              :items="registrations"
              :loading="loading"
              :items-per-page="20"
            >
              <template #item.date="{ item }">
                {{ formatDate(item.date) }}
              </template>
              
              <template #item.time="{ item }">
                {{ item.start_time }} - {{ item.end_time }}
              </template>
              
              <template #item.type="{ item }">
                <VChip :color="getTypeColor(item.type)" size="small">
                  <VIcon size="16" class="mr-1">{{ getTypeIcon(item.type) }}</VIcon>
                  {{ getTypeLabel(item.type) }}
                </VChip>
              </template>
              
              <template #item.description="{ item }">
                <div>{{ item.description || '-' }}</div>
                <div v-if="item.lesson_schedule" class="text-caption text-disabled">
                  Les: {{ item.lesson_schedule.group?.name }}
                </div>
              </template>
              
              <template #item.hours="{ item }">
                {{ item.hours }} uur
              </template>
              
              <template #item.amount="{ item }">
                €{{ formatAmount(item.total_amount) }}
                <div class="text-caption text-disabled">
                  €{{ item.hourly_rate }}/uur
                </div>
              </template>
              
              <template #item.status="{ item }">
                <VChip 
                  :color="getRegistrationStatusColor(item.status)"
                  size="small"
                >
                  {{ getStatusLabel(item.status) }}
                </VChip>
              </template>
              
              <template #item.actions="{ item }">
                <VBtn
                  v-if="item.status === 'pending' && isAdmin"
                  icon="tabler-check"
                  size="x-small"
                  color="success"
                  variant="text"
                  @click="approveRegistration(item)"
                  title="Goedkeuren"
                />
                <VBtn
                  v-if="item.status === 'pending' && isAdmin"
                  icon="tabler-x"
                  size="x-small"
                  color="error"
                  variant="text"
                  @click="rejectRegistration(item)"
                  title="Afwijzen"
                />
                <VBtn
                  v-if="canEdit(item)"
                  icon="tabler-edit"
                  size="x-small"
                  variant="text"
                  @click="editRegistration(item)"
                />
                <VBtn
                  v-if="canDelete(item)"
                  icon="tabler-trash"
                  size="x-small"
                  variant="text"
                  color="error"
                  @click="deleteRegistration(item)"
                />
              </template>
              
              <template #bottom>
                <!-- Summary Row -->
                <div class="pa-4 border-t">
                  <VRow align="center">
                    <VCol cols="12" md="6">
                      <strong>Totaal deze pagina:</strong>
                    </VCol>
                    <VCol cols="12" md="2" class="text-right">
                      <strong>{{ pageTotal.hours }} uur</strong>
                    </VCol>
                    <VCol cols="12" md="2" class="text-right">
                      <strong>€{{ formatAmount(pageTotal.amount) }}</strong>
                    </VCol>
                    <VCol cols="12" md="2">
                      <VBtn
                        v-if="canSubmitMonth"
                        color="primary"
                        @click="submitMonth"
                        block
                      >
                        Indienen
                      </VBtn>
                    </VCol>
                  </VRow>
                </div>
              </template>
            </VDataTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Add/Edit Dialog -->
    <VDialog v-model="showAddDialog" max-width="600">
      <VCard>
        <VCardTitle>
          {{ editingRegistration ? 'Uren Bewerken' : 'Nieuwe Uren Registratie' }}
        </VCardTitle>
        <VCardText>
          <VRow>
            <VCol cols="12" v-if="isAdmin">
              <VSelect
                v-model="form.user_id"
                label="Trainer"
                :items="trainers"
                item-title="name"
                item-value="id"
                :rules="[v => !!v || 'Trainer is verplicht']"
              />
            </VCol>
            
            <VCol cols="12">
              <VTextField
                v-model="form.date"
                label="Datum"
                type="date"
                :rules="[v => !!v || 'Datum is verplicht']"
              />
            </VCol>
            
            <VCol cols="6">
              <VTextField
                v-model="form.start_time"
                label="Start tijd"
                type="time"
                :rules="[v => !!v || 'Start tijd is verplicht']"
              />
            </VCol>
            
            <VCol cols="6">
              <VTextField
                v-model="form.end_time"
                label="Eind tijd"
                type="time"
                :rules="[v => !!v || 'Eind tijd is verplicht']"
              />
            </VCol>
            
            <VCol cols="12">
              <VSelect
                v-model="form.type"
                label="Type"
                :items="typeOptions"
                :rules="[v => !!v || 'Type is verplicht']"
              />
            </VCol>
            
            <VCol cols="12">
              <VTextField
                v-model="form.description"
                label="Beschrijving"
                placeholder="Bijv: Groep beginners, Voorbereiding toernooi"
              />
            </VCol>
            
            <VCol cols="12">
              <VTextarea
                v-model="form.notes"
                label="Notities"
                rows="3"
              />
            </VCol>
            
            <VCol cols="12" v-if="isAdmin">
              <VTextField
                v-model.number="form.hourly_rate"
                label="Uurtarief (€)"
                type="number"
                step="0.01"
                min="0"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="cancelEdit">Annuleren</VBtn>
          <VBtn color="primary" @click="saveRegistration">Opslaan</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Reject Dialog -->
    <VDialog v-model="showRejectDialog" max-width="500">
      <VCard>
        <VCardTitle>Uren Afwijzen</VCardTitle>
        <VCardText>
          <VTextarea
            v-model="rejectReason"
            label="Reden voor afwijzing"
            rows="3"
            :rules="[v => !!v || 'Reden is verplicht']"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showRejectDialog = false">Annuleren</VBtn>
          <VBtn color="error" @click="confirmReject">Afwijzen</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Import from Schedule Dialog -->
    <VDialog v-model="showImportDialog" max-width="500">
      <VCard>
        <VCardTitle>Importeer uit Lessenrooster</VCardTitle>
        <VCardText>
          <p class="mb-4">
            Importeer automatisch alle voltooide lessen die nog niet zijn geregistreerd.
          </p>
          
          <VSelect
            v-model="importTrainer"
            label="Trainer"
            :items="trainers"
            item-title="name"
            item-value="id"
            class="mb-4"
          />
          
          <VRow>
            <VCol cols="6">
              <VTextField
                v-model="importDateFrom"
                label="Van datum"
                type="date"
              />
            </VCol>
            <VCol cols="6">
              <VTextField
                v-model="importDateTo"
                label="Tot datum"
                type="date"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showImportDialog = false">Annuleren</VBtn>
          <VBtn 
            color="primary" 
            @click="importFromSchedule"
            :loading="importing"
          >
            Importeer
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from '@/plugins/axios'

definePage({
  meta: {
    requiresAuth: true,
  },
})

// Data refs
const loading = ref(false)
const registrations = ref([])
const trainers = ref([])
const monthlyStatus = ref('draft')
const showAddDialog = ref(false)
const showRejectDialog = ref(false)
const showImportDialog = ref(false)
const editingRegistration = ref(null)
const rejectingRegistration = ref(null)
const rejectReason = ref('')
const importing = ref(false)

// Filter refs
const selectedMonth = ref(new Date().getMonth() + 1)
const selectedYear = ref(new Date().getFullYear())
const selectedTrainer = ref(null)
const filterStatus = ref(null)

// Form refs
const form = ref({
  user_id: null,
  date: '',
  start_time: '',
  end_time: '',
  type: 'lesson',
  description: '',
  notes: '',
  hourly_rate: null,
})

// Import refs
const importTrainer = ref(null)
const importDateFrom = ref('')
const importDateTo = ref('')

// User info
const currentUser = ref(null)
const isAdmin = ref(false)

// Table headers
const headers = [
  { title: 'Datum', key: 'date' },
  { title: 'Tijd', key: 'time', sortable: false },
  { title: 'Type', key: 'type' },
  { title: 'Beschrijving', key: 'description', sortable: false },
  { title: 'Uren', key: 'hours' },
  { title: 'Bedrag', key: 'amount' },
  { title: 'Status', key: 'status' },
  { title: 'Acties', key: 'actions', sortable: false },
]

// Options
const monthOptions = [
  { title: 'Januari', value: 1 },
  { title: 'Februari', value: 2 },
  { title: 'Maart', value: 3 },
  { title: 'April', value: 4 },
  { title: 'Mei', value: 5 },
  { title: 'Juni', value: 6 },
  { title: 'Juli', value: 7 },
  { title: 'Augustus', value: 8 },
  { title: 'September', value: 9 },
  { title: 'Oktober', value: 10 },
  { title: 'November', value: 11 },
  { title: 'December', value: 12 },
]

const yearOptions = [2024, 2025, 2026]

const statusOptions = [
  { title: 'Alle', value: null },
  { title: 'In afwachting', value: 'pending' },
  { title: 'Goedgekeurd', value: 'approved' },
  { title: 'Afgewezen', value: 'rejected' },
]

const typeOptions = [
  { title: 'Les', value: 'lesson' },
  { title: 'Voorbereiding', value: 'preparation' },
  { title: 'Vergadering', value: 'meeting' },
  { title: 'Toernooi', value: 'tournament' },
  { title: 'Overig', value: 'other' },
]

// Computed
const currentMonth = computed(() => {
  const month = monthOptions.find(m => m.value === selectedMonth.value)
  return `${month?.title} ${selectedYear.value}`
})

const totalHours = computed(() => {
  return registrations.value.reduce((sum, r) => sum + parseFloat(r.hours), 0).toFixed(2)
})

const approvedAmount = computed(() => {
  return registrations.value
    .filter(r => r.status === 'approved')
    .reduce((sum, r) => sum + parseFloat(r.total_amount), 0)
    .toFixed(2)
})

const pendingAmount = computed(() => {
  return registrations.value
    .filter(r => r.status === 'pending')
    .reduce((sum, r) => sum + parseFloat(r.total_amount), 0)
    .toFixed(2)
})

const pendingRegistrations = computed(() => {
  return registrations.value.filter(r => r.status === 'pending')
})

const pageTotal = computed(() => {
  const hours = registrations.value.reduce((sum, r) => sum + parseFloat(r.hours), 0)
  const amount = registrations.value.reduce((sum, r) => sum + parseFloat(r.total_amount), 0)
  return { hours: hours.toFixed(2), amount }
})

const canSubmitMonth = computed(() => {
  return !isAdmin.value && 
         monthlyStatus.value === 'draft' && 
         pendingRegistrations.value.length === 0 &&
         registrations.value.length > 0
})

// Methods
const loadData = async () => {
  loading.value = true
  try {
    const params = {
      year: selectedYear.value,
      month: selectedMonth.value,
    }
    
    if (selectedTrainer.value) {
      params.trainer_id = selectedTrainer.value
    }
    
    if (filterStatus.value) {
      params.status = filterStatus.value
    }
    
    const response = await axios.get('/trainer-hours', { params })
    registrations.value = response.data.data.data || []
    
    // Load monthly summary
    const summaryResponse = await axios.get('/trainer-hours/monthly-summary', {
      params: {
        year: selectedYear.value,
        month: selectedMonth.value,
        trainer_id: selectedTrainer.value
      }
    })
    
    if (summaryResponse.data.data.summary) {
      monthlyStatus.value = summaryResponse.data.data.summary.status
    }
  } catch (error) {
    console.error('Error loading data:', error)
  } finally {
    loading.value = false
  }
}

const loadTrainers = async () => {
  try {
    const response = await axios.get('/trainers')
    trainers.value = response.data.data || []
  } catch (error) {
    console.error('Error loading trainers:', error)
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  const days = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za']
  return `${days[d.getDay()]} ${d.toLocaleDateString('nl-BE')}`
}

const formatAmount = (amount) => {
  return new Intl.NumberFormat('nl-NL', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(amount || 0)
}

const getTypeColor = (type) => {
  const colors = {
    lesson: 'primary',
    preparation: 'info',
    meeting: 'warning',
    tournament: 'success',
    other: 'secondary'
  }
  return colors[type] || 'default'
}

const getTypeIcon = (type) => {
  const icons = {
    lesson: 'tabler-school',
    preparation: 'tabler-notebook',
    meeting: 'tabler-users',
    tournament: 'tabler-trophy',
    other: 'tabler-dots'
  }
  return icons[type] || 'tabler-clock'
}

const getTypeLabel = (type) => {
  const option = typeOptions.find(t => t.value === type)
  return option?.title || type
}

const getStatusColor = (status) => {
  const colors = {
    draft: 'secondary',
    submitted: 'warning',
    approved: 'success',
    paid: 'info',
    rejected: 'error'
  }
  return colors[status] || 'default'
}

const getRegistrationStatusColor = (status) => {
  const colors = {
    pending: 'warning',
    approved: 'success',
    rejected: 'error',
    paid: 'info'
  }
  return colors[status] || 'default'
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'In afwachting',
    approved: 'Goedgekeurd',
    rejected: 'Afgewezen',
    paid: 'Betaald',
    draft: 'Concept',
    submitted: 'Ingediend'
  }
  return labels[status] || status
}

const canEdit = (item) => {
  if (isAdmin.value) return true
  return item.user_id === currentUser.value?.id && item.status !== 'approved'
}

const canDelete = (item) => {
  if (isAdmin.value) return true
  return item.user_id === currentUser.value?.id && item.status !== 'approved'
}

const editRegistration = (item) => {
  editingRegistration.value = item
  form.value = {
    user_id: item.user_id,
    date: item.date,
    start_time: item.start_time,
    end_time: item.end_time,
    type: item.type,
    description: item.description || '',
    notes: item.notes || '',
    hourly_rate: item.hourly_rate,
  }
  showAddDialog.value = true
}

const cancelEdit = () => {
  showAddDialog.value = false
  editingRegistration.value = null
  form.value = {
    user_id: null,
    date: '',
    start_time: '',
    end_time: '',
    type: 'lesson',
    description: '',
    notes: '',
    hourly_rate: null,
  }
}

const saveRegistration = async () => {
  try {
    if (editingRegistration.value) {
      await axios.put(`/trainer-hours/${editingRegistration.value.id}`, form.value)
    } else {
      await axios.post('/trainer-hours', form.value)
    }
    
    cancelEdit()
    await loadData()
  } catch (error) {
    console.error('Error saving registration:', error)
    alert('Fout bij opslaan registratie')
  }
}

const deleteRegistration = async (item) => {
  if (!confirm('Weet je zeker dat je deze registratie wilt verwijderen?')) {
    return
  }
  
  try {
    await axios.delete(`/trainer-hours/${item.id}`)
    await loadData()
  } catch (error) {
    console.error('Error deleting registration:', error)
    alert('Fout bij verwijderen registratie')
  }
}

const approveRegistration = async (item) => {
  try {
    await axios.post(`/trainer-hours/${item.id}/approve`)
    await loadData()
  } catch (error) {
    console.error('Error approving registration:', error)
    alert('Fout bij goedkeuren registratie')
  }
}

const rejectRegistration = (item) => {
  rejectingRegistration.value = item
  rejectReason.value = ''
  showRejectDialog.value = true
}

const confirmReject = async () => {
  if (!rejectReason.value) {
    alert('Geef een reden op voor afwijzing')
    return
  }
  
  try {
    await axios.post(`/trainer-hours/${rejectingRegistration.value.id}/reject`, {
      reason: rejectReason.value
    })
    
    showRejectDialog.value = false
    rejectingRegistration.value = null
    rejectReason.value = ''
    await loadData()
  } catch (error) {
    console.error('Error rejecting registration:', error)
    alert('Fout bij afwijzen registratie')
  }
}

const approveAll = async () => {
  if (!confirm(`Weet je zeker dat je alle ${pendingRegistrations.value.length} registraties wilt goedkeuren?`)) {
    return
  }
  
  try {
    await axios.post('/trainer-hours/bulk-approve', {
      registration_ids: pendingRegistrations.value.map(r => r.id)
    })
    await loadData()
  } catch (error) {
    console.error('Error bulk approving:', error)
    alert('Fout bij goedkeuren registraties')
  }
}

const submitMonth = async () => {
  if (!confirm('Weet je zeker dat je deze maand wilt indienen voor goedkeuring?')) {
    return
  }
  
  try {
    await axios.post('/trainer-hours/submit-monthly', {
      year: selectedYear.value,
      month: selectedMonth.value,
    })
    
    alert('Maandoverzicht ingediend voor goedkeuring')
    await loadData()
  } catch (error) {
    console.error('Error submitting month:', error)
    alert(error.response?.data?.message || 'Fout bij indienen maandoverzicht')
  }
}

const importFromSchedule = async () => {
  if (!importTrainer.value || !importDateFrom.value || !importDateTo.value) {
    alert('Vul alle velden in')
    return
  }
  
  importing.value = true
  try {
    const response = await axios.post('/trainer-hours/import-from-schedule', {
      trainer_id: importTrainer.value,
      date_from: importDateFrom.value,
      date_to: importDateTo.value,
    })
    
    alert(response.data.message || 'Lessen geïmporteerd')
    showImportDialog.value = false
    await loadData()
  } catch (error) {
    console.error('Error importing:', error)
    alert('Fout bij importeren lessen')
  } finally {
    importing.value = false
  }
}

const exportHours = () => {
  const params = new URLSearchParams({
    year: selectedYear.value,
    month: selectedMonth.value,
  })
  
  if (selectedTrainer.value) {
    params.append('trainer_id', selectedTrainer.value)
  }
  
  window.open(`/api/trainer-hours/export?${params}`, '_blank')
}

// Get current user info
const getCurrentUser = async () => {
  try {
    const response = await axios.get('/auth/user')
    currentUser.value = response.data.userData
    isAdmin.value = currentUser.value.role === 'admin'
    
    // If trainer, set default trainer selection
    if (!isAdmin.value && currentUser.value.role === 'trainer') {
      selectedTrainer.value = currentUser.value.id
    }
  } catch (error) {
    console.error('Error getting user info:', error)
  }
}

// Watchers
watch([selectedMonth, selectedYear, selectedTrainer, filterStatus], () => {
  loadData()
})

// Lifecycle
onMounted(async () => {
  await getCurrentUser()
  await loadTrainers()
  await loadData()
  
  // Set default dates for import
  const today = new Date()
  const firstDay = new Date(today.getFullYear(), today.getMonth(), 1)
  const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0)
  
  importDateFrom.value = firstDay.toISOString().split('T')[0]
  importDateTo.value = lastDay.toISOString().split('T')[0]
})
</script>

<style scoped>
.border-t {
  border-top: 1px solid rgba(0, 0, 0, 0.12);
}
</style>
