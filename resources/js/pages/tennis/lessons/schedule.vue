<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Lessenrooster</h1>
            <p class="text-body-1 mt-1" v-if="lessonPackage">{{ lessonPackage.name }}</p>
          </div>
          <div>
            <VBtn 
              variant="outlined"
              :to="{ name: 'tennis-lessons-manage', params: { id: packageId } }"
              class="mr-2"
            >
              Terug naar Groepen
            </VBtn>
            <VBtn 
              color="primary"
              @click="showGenerateDialog = true"
            >
              Genereer Rooster
            </VBtn>
          </div>
        </div>
      </VCol>
    </VRow>

    <!-- Calendar/Schedule View Toggle met Tabs -->
    <VRow>
      <VCol cols="12">
        <VTabs v-model="viewMode" class="mb-4">
          <VTab value="list" prepend-icon="tabler-list">
            Lijst
          </VTab>
          <VTab value="calendar" prepend-icon="tabler-calendar">
            Kalender
          </VTab>
        </VTabs>
      </VCol>
    </VRow>

    <!-- List View -->
    <VRow v-if="viewMode === 'list'">
      <VCol cols="12">
        <VCard v-for="group in groups" :key="group.id" class="mb-4">
          <VCardItem>
            <VCardTitle>
              {{ group.name }}
              <VChip size="small" class="ml-2">
                {{ group.registrations?.length || 0 }} deelnemers
              </VChip>
            </VCardTitle>
            <template #append>
              <VBtn
                size="small"
                variant="outlined"
                @click="generateGroupSchedule(group.id)"
              >
                Genereer Lessen
              </VBtn>
            </template>
          </VCardItem>
          
          <VCardText>
            <VTable>
              <thead>
                <tr>
                  <th>Datum</th>
                  <th>Tijd</th>
                  <th>Locatie</th>
                  <th>Trainer</th>
                  <th>Status</th>
                  <th>Aanwezigheid</th>
                  <th>Acties</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="lesson in getGroupLessons(group.id)" :key="lesson.id">
                  <td>{{ formatDate(lesson.lesson_date) }}</td>
                  <td>{{ lesson.start_time }} - {{ lesson.end_time }}</td>
                  <td>{{ lesson.location?.name || '-' }}</td>
                  <td>{{ group.trainer?.name || '-' }}</td>
                  <td>
                    <VChip 
                      :color="getStatusColor(lesson.status)"
                      size="small"
                    >
                      {{ lesson.status }}
                    </VChip>
                  </td>
                  <td>
                    <VBtn
                      size="small"
                      variant="tonal"
                      color="primary"
                      @click="openAttendance(lesson, group)"
                    >
                      <VIcon size="16" class="me-1">tabler-users</VIcon>
                      Registreer
                    </VBtn>
                  </td>
                  <td>
                    <VBtn
                      icon="tabler-edit"
                      size="x-small"
                      variant="text"
                      @click="editLesson(lesson)"
                    />
                    <VBtn
                      v-if="lesson.status === 'scheduled'"
                      icon="tabler-x"
                      size="x-small"
                      variant="text"
                      color="error"
                      @click="cancelLesson(lesson)"
                    />
                  </td>
                </tr>
                <tr v-if="!getGroupLessons(group.id).length">
                  <td colspan="6" class="text-center py-4">
                    Nog geen lessen ingepland
                  </td>
                </tr>
              </tbody>
            </VTable>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Calendar View -->
    <VRow v-else>
      <VCol cols="12">
        <VCard>
          <VCardText>
            <div id="calendar" style="min-height: 600px;">
              <!-- Hier zou je een calendar component zoals FullCalendar kunnen gebruiken -->
              <p class="text-center py-8">Kalender weergave komt binnenkort...</p>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Generate Schedule Dialog -->
    <VDialog v-model="showGenerateDialog" max-width="500">
      <VCard>
        <VCardTitle>Genereer Lessenrooster</VCardTitle>
        <VCardText>
          <VSelect
            v-model="generateForm.group_id"
            label="Selecteer Groep"
            :items="unscheduledGroups"
            item-title="name"
            item-value="id"
            class="mb-3"
          />
          
          <VAlert type="info" class="mb-3" v-if="selectedGroup">
            <div class="mb-2"><strong>{{ selectedGroup.name }}</strong></div>
            <div>Periode: {{ formatSimpleDate(lessonPackage?.start_date) }} - {{ formatSimpleDate(lessonPackage?.end_date) }}</div>
            <div>Lesdagen: {{ formatDays(selectedGroup.schedule_days) || 'Gebruikt pakket dagen' }}</div>
            <div>Lestijd: {{ selectedGroup.default_start_time || '19:00' }} - {{ selectedGroup.default_end_time || '20:00' }}</div>
            <div>Totaal lessen: {{ lessonPackage?.total_lessons }}</div>
          </VAlert>
          
          <VAlert type="warning" class="mb-3" v-if="hasExistingSchedule">
            Deze groep heeft al {{ existingLessonsCount }} lessen ingepland.
            <VCheckbox
              v-model="generateForm.regenerate"
              label="Overschrijf bestaand rooster"
              hide-details
              class="mt-2"
            />
          </VAlert>
          
          <VExpansionPanels v-model="showAdvanced">
            <VExpansionPanel>
              <VExpansionPanelTitle>Geavanceerde opties</VExpansionPanelTitle>
              <VExpansionPanelText>
                <p class="text-body-2 mb-2">Optioneel: overschrijf pakket datums</p>
                <VTextField
                  v-model="generateForm.start_date"
                  label="Start datum (optioneel)"
                  type="date"
                  class="mb-3"
                  clearable
                />
                
                <VTextField
                  v-model="generateForm.end_date"
                  label="Eind datum (optioneel)"
                  type="date"
                  clearable
                />
              </VExpansionPanelText>
            </VExpansionPanel>
          </VExpansionPanels>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="cancelGenerate">Annuleer</VBtn>
          <VBtn 
            color="primary" 
            @click="generateSchedule"
            :disabled="!generateForm.group_id"
          >
            Genereer {{ hasExistingSchedule && generateForm.regenerate ? 'Opnieuw' : '' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>

    <!-- Edit Lesson Dialog -->
    <VDialog v-model="showEditDialog" max-width="500">
      <VCard>
        <VCardTitle>Les Bewerken</VCardTitle>
        <VCardText>
          <VTextField
            v-model="editForm.lesson_date"
            label="Datum"
            type="date"
            class="mb-3"
          />
          
          <VRow>
            <VCol cols="6">
              <VTextField
                v-model="editForm.start_time"
                label="Start tijd"
                type="time"
              />
            </VCol>
            <VCol cols="6">
              <VTextField
                v-model="editForm.end_time"
                label="Eind tijd"
                type="time"
              />
            </VCol>
          </VRow>
          
          <VSelect
            v-model="editForm.location_id"
            label="Locatie"
            :items="locations"
            item-title="name"
            item-value="id"
            class="mb-3"
          />
          
          <VSelect
            v-model="editForm.status"
            label="Status"
            :items="statusOptions"
            class="mb-3"
          />
          
          <VTextarea
            v-model="editForm.notes"
            label="Notities"
            rows="3"
          />
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="showEditDialog = false">Annuleer</VBtn>
          <VBtn color="primary" @click="saveLesson">Opslaan</VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
  
  <!-- Attendance Dialog Component -->
  <AttendanceDialog 
    v-model="showAttendanceDialog"
    :lesson="selectedLesson"
    :package-id="packageId"
    @saved="onAttendanceSaved"
  />
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import axios from '@/plugins/axios'
import AttendanceDialog from '@/components/tennis/AttendanceDialog.vue'

definePage({
  meta: {
    requiresAuth: true,
  },
})

const route = useRoute()
const packageId = route.params.id

const lessonPackage = ref(null)
const groups = ref([])
const schedules = ref([])
const locations = ref([])
const viewMode = ref('list')
const showGenerateDialog = ref(false)
const showEditDialog = ref(false)
const showAttendanceDialog = ref(false)
const selectedLesson = ref(null)

const generateForm = ref({
  group_id: null,
  start_date: null, // Optioneel, gebruik pakket datum als null
  end_date: null,   // Optioneel, gebruik pakket datum als null
  regenerate: false
})

const showAdvanced = ref(null)

const selectedGroup = computed(() => {
  if (!generateForm.value.group_id) return null
  return groups.value.find(g => g.id === generateForm.value.group_id)
})

const hasExistingSchedule = computed(() => {
  if (!generateForm.value.group_id) return false
  return getGroupLessons(generateForm.value.group_id).length > 0
})

const existingLessonsCount = computed(() => {
  if (!generateForm.value.group_id) return 0
  return getGroupLessons(generateForm.value.group_id).length
})

const unscheduledGroups = computed(() => {
  // Toon alle groepen, maar markeer degene die al een rooster hebben
  return groups.value.map(g => ({
    ...g,
    name: g.name + (getGroupLessons(g.id).length > 0 ? ' (heeft rooster)' : '')
  }))
})

const editForm = ref({
  id: null,
  group_id: null,
  lesson_date: '',
  start_time: '',
  end_time: '',
  location_id: null,
  status: 'scheduled',
  notes: ''
})

const statusOptions = [
  { title: 'Gepland', value: 'scheduled' },
  { title: 'Voltooid', value: 'completed' },
  { title: 'Geannuleerd', value: 'cancelled' }
]

const getGroupLessons = (groupId) => {
  return schedules.value.filter(s => s.lesson_group_id === groupId)
}

const getStatusColor = (status) => {
  const colors = {
    scheduled: 'primary',
    completed: 'success',
    cancelled: 'error'
  }
  return colors[status] || 'default'
}

const formatDate = (date) => {
  if (!date) return '-'
  const d = new Date(date)
  const days = ['Zo', 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za']
  return `${days[d.getDay()]} ${d.toLocaleDateString('nl-BE')}`
}

const formatSimpleDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const formatDays = (days) => {
  if (!days || days.length === 0) return null
  const dayMap = {
    monday: 'Ma',
    tuesday: 'Di',
    wednesday: 'Wo',
    thursday: 'Do',
    friday: 'Vr',
    saturday: 'Za',
    sunday: 'Zo'
  }
  return days.map(d => dayMap[d] || d).join(', ')
}

const loadData = async () => {
  try {
    // Load package with groups
    const packageResponse = await axios.get(`/lessons/packages/${packageId}`)
    lessonPackage.value = packageResponse.data.data
    groups.value = packageResponse.data.data.groups || []
    
    // Clear existing schedules before loading new ones
    schedules.value = []
    
    // Load schedules for all groups
    for (const group of groups.value) {
      try {
        const scheduleResponse = await axios.get(`/lessons/packages/${packageId}/groups/${group.id}/schedule`)
        // Push only the schedules for this specific group
        if (scheduleResponse.data.data && scheduleResponse.data.data.length > 0) {
          schedules.value.push(...scheduleResponse.data.data)
        }
      } catch (error) {
        console.error(`Error loading schedule for group ${group.id}:`, error)
        // Continue with next group even if one fails
      }
    }
    
    // Load locations
    const locationsResponse = await axios.get('/lessons/locations')
    locations.value = locationsResponse.data.data || []
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

const generateGroupSchedule = async (groupId) => {
  generateForm.value.group_id = groupId
  generateForm.value.start_date = null
  generateForm.value.end_date = null
  generateForm.value.regenerate = false
  showAdvanced.value = null
  showGenerateDialog.value = true
}

const cancelGenerate = () => {
  showGenerateDialog.value = false
  generateForm.value = {
    group_id: null,
    start_date: null,
    end_date: null,
    regenerate: false
  }
  showAdvanced.value = null
}

const generateSchedule = async () => {
  if (!generateForm.value.group_id) {
    alert('Selecteer een groep')
    return
  }
  
  // Check of we moeten regenereren
  if (hasExistingSchedule.value && !generateForm.value.regenerate) {
    alert('Deze groep heeft al lessen. Vink "Overschrijf bestaand rooster" aan om opnieuw te genereren.')
    return
  }
  
  try {
    const payload = {
      regenerate: generateForm.value.regenerate
    }
    
    // Voeg alleen datums toe als ze zijn ingevuld (advanced options)
    if (generateForm.value.start_date) {
      payload.start_date = generateForm.value.start_date
    }
    if (generateForm.value.end_date) {
      payload.end_date = generateForm.value.end_date
    }
    
    const generatedGroupId = generateForm.value.group_id
    
    const response = await axios.post(
      `/lessons/packages/${packageId}/groups/${generatedGroupId}/schedule`, 
      payload
    )
    
    // Sluit dialog en reset form
    showGenerateDialog.value = false
    generateForm.value = {
      group_id: null,
      start_date: null,
      end_date: null,
      regenerate: false
    }
    
    // Herlaad data proper - dit cleared de array en laadt alles opnieuw
    await loadData()
    
    alert(response.data.message || 'Rooster succesvol gegenereerd')
  } catch (error) {
    console.error('Error generating schedule:', error)
    if (error.response?.data?.message) {
      alert(error.response.data.message)
    } else {
      alert('Fout bij genereren rooster')
    }
  }
}

const editLesson = (lesson) => {
  editForm.value = {
    id: lesson.id,
    group_id: lesson.lesson_group_id,
    lesson_date: lesson.lesson_date,
    start_time: lesson.start_time,
    end_time: lesson.end_time,
    location_id: lesson.location_id,
    status: lesson.status,
    notes: lesson.notes || ''
  }
  showEditDialog.value = true
}

const saveLesson = async () => {
  try {
    await axios.put(`/lessons/packages/${packageId}/groups/${editForm.value.group_id}/schedule/${editForm.value.id}`, {
      lesson_date: editForm.value.lesson_date,
      start_time: editForm.value.start_time,
      end_time: editForm.value.end_time,
      location_id: editForm.value.location_id,
      status: editForm.value.status,
      notes: editForm.value.notes
    })
    
    showEditDialog.value = false
    await loadData()
  } catch (error) {
    console.error('Error updating lesson:', error)
    alert('Fout bij opslaan les')
  }
}

const cancelLesson = async (lesson) => {
  if (!confirm('Weet je zeker dat je deze les wilt annuleren?')) return
  
  try {
    await axios.post(`/lessons/packages/${packageId}/groups/${lesson.lesson_group_id}/schedule/${lesson.id}/cancel`, {
      reason: 'Les geannuleerd door beheerder'
    })
    await loadData()
  } catch (error) {
    console.error('Error cancelling lesson:', error)
    alert('Fout bij annuleren les')
  }
}

const openAttendance = (lesson, group) => {
  selectedLesson.value = {
    ...lesson,
    group: group
  }
  showAttendanceDialog.value = true
}

const onAttendanceSaved = () => {
  loadData()
  alert('Aanwezigheid opgeslagen')
}

onMounted(() => {
  loadData()
})
</script>
