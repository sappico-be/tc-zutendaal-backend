<template>
  <VContainer>
    <!-- Header -->
    <VRow>
      <VCol cols="12">
        <div class="d-flex justify-space-between align-center mb-6">
          <div>
            <h1 class="text-h4">Beheer Inschrijvingen</h1>
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

    <!-- Statistics -->
    <VRow>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4">{{ registrations.length }}</div>
            <div class="text-body-2">Inschrijvingen</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4">{{ assignedCount }}</div>
            <div class="text-body-2">Ingedeeld</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4">{{ groups.length }}</div>
            <div class="text-body-2">Groepen</div>
          </VCardText>
        </VCard>
      </VCol>
      <VCol cols="12" sm="3">
        <VCard>
          <VCardText class="text-center">
            <div class="text-h4">{{ unassignedCount }}</div>
            <div class="text-body-2">Nog in te delen</div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Main Content -->
    <VRow class="mt-6">
      <!-- Registrations List -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Inschrijvingen (niet ingedeeld)</VCardTitle>
          </VCardItem>
          <VCardText>
            <VList v-if="unassignedRegistrations.length > 0">
              <VListItem
                v-for="reg in unassignedRegistrations"
                :key="reg.id"
                :class="{ 'bg-blue-lighten-5': selectedRegistrations.includes(reg.id) }"
                @click="toggleRegistration(reg.id)"
              >
                <template #prepend>
                  <VCheckbox
                    :model-value="selectedRegistrations.includes(reg.id)"
                    @update:model-value="toggleRegistration(reg.id)"
                    @click.stop
                  />
                </template>
                
                <VListItemTitle>{{ reg.user.name }}</VListItemTitle>
                <VListItemSubtitle>
                  <div>Niveau: {{ getLevelLabel(reg.level) }}</div>
                  <div>Beschikbaar: {{ formatDays(reg.available_days) }}</div>
                  <div v-if="reg.preferred_partners?.length">
                    Samen met: {{ reg.preferred_partners.join(', ') }}
                  </div>
                </VListItemSubtitle>
              </VListItem>
            </VList>
            <div v-else class="text-center py-4">
              <p class="text-body-2">Alle leden zijn ingedeeld</p>
            </div>
          </VCardText>
        </VCard>
      </VCol>

      <!-- Groups -->
      <VCol cols="12" md="6">
        <VCard>
          <VCardItem>
            <VCardTitle>Groepen</VCardTitle>
            <template #append>
              <VBtn
                size="small"
                color="primary"
                @click="showCreateGroup = true"
              >
                Nieuwe Groep
              </VBtn>
            </template>
          </VCardItem>
          <VCardText>
            <VExpansionPanels v-if="groups.length > 0">
              <VExpansionPanel
                v-for="group in groups"
                :key="group.id"
              >
                <VExpansionPanelTitle>
                  <div class="d-flex justify-space-between w-100">
                    <span>{{ group.name }} ({{ group.registrations?.length || 0 }}/{{ group.max_participants }})</span>
                    <VChip
                      size="small"
                      :color="getLevelColor(group.level)"
                      class="mr-2"
                    >
                      {{ getLevelLabel(group.level) }}
                    </VChip>
                  </div>
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <div class="mb-3">
                    <p><strong>Trainer:</strong> {{ group.trainer?.name || 'Niet toegewezen' }}</p>
                    <p><strong>Locatie:</strong> {{ group.location?.name || 'Niet toegewezen' }}</p>
                    <p><strong>Niveau:</strong> {{ getLevelLabel(group.level) }}</p>
                    <p><strong>Dagen:</strong> {{ formatDays(group.schedule_days) }}</p>
                    <p v-if="group.default_start_time">
                      <strong>Tijd:</strong> {{ group.default_start_time }} - {{ group.default_end_time }}
                    </p>
                  </div>
                  
                  <VDivider class="my-3" />
                  
                  <p class="font-weight-bold mb-2">Leden ({{ group.registrations?.length || 0 }}):</p>
                  <VList dense v-if="group.registrations?.length > 0">
                    <VListItem
                      v-for="reg in group.registrations"
                      :key="reg.id"
                    >
                      <template #default>
                        {{ reg.user.name }}
                      </template>
                      <template #append>
                        <VBtn
                          icon="tabler-x"
                          size="x-small"
                          variant="text"
                          color="error"
                          @click="removeFromGroup(reg.id)"
                        />
                      </template>
                    </VListItem>
                  </VList>
                  <p v-else class="text-body-2 text-center py-2">Nog geen leden in deze groep</p>
                  
                  <VBtn
                    v-if="selectedRegistrations.length > 0 && (group.registrations?.length || 0) < group.max_participants"
                    size="small"
                    color="primary"
                    class="mt-3"
                    @click="assignToGroup(group.id)"
                  >
                    Voeg {{ selectedRegistrations.length }} geselecteerde toe
                  </VBtn>
                  
                  <div class="mt-3 d-flex gap-2">
                    <VBtn
                      size="small"
                      variant="outlined"
                      @click="editGroup(group)"
                    >
                      Bewerk
                    </VBtn>
                    <VBtn
                      size="small"
                      variant="outlined"
                      color="error"
                      @click="deleteGroup(group.id)"
                    >
                      Verwijder
                    </VBtn>
                  </div>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
            <div v-else class="text-center py-4">
              <p class="text-body-2">Nog geen groepen aangemaakt</p>
            </div>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Create/Edit Group Dialog -->
    <VDialog v-model="showCreateGroup" max-width="600">
      <VCard>
        <VCardTitle>{{ editingGroup ? 'Groep Bewerken' : 'Nieuwe Groep' }}</VCardTitle>
        <VCardText>
          <VTextField
            v-model="newGroup.name"
            label="Naam"
            class="mb-3"
            :rules="[v => !!v || 'Naam is verplicht']"
          />
          
          <VSelect
            v-model="newGroup.level"
            label="Niveau"
            :items="levelOptions"
            class="mb-3"
          />
          
          <VTextField
            v-model.number="newGroup.max_participants"
            label="Max deelnemers"
            type="number"
            class="mb-3"
            :rules="[v => v > 0 || 'Moet minimaal 1 zijn']"
          />
          
          <VSelect
            v-model="newGroup.trainer_id"
            label="Trainer"
            :items="trainers"
            item-title="name"
            item-value="id"
            clearable
            class="mb-3"
            :loading="loadingTrainers"
          />
          
          <VSelect
            v-model="newGroup.location_id"
            label="Locatie"
            :items="locations"
            item-title="name"
            item-value="id"
            clearable
            class="mb-3"
            :loading="loadingLocations"
          />
          
          <p class="text-body-1 mb-2">Lesdagen:</p>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <VCheckbox
              v-for="day in weekDays"
              :key="day.value"
              v-model="newGroup.schedule_days"
              :label="day.label"
              :value="day.value"
              hide-details
              density="compact"
            />
          </div>
          
          <VRow class="mt-3">
            <VCol cols="6">
              <VTextField
                v-model="newGroup.default_start_time"
                label="Start tijd"
                type="time"
              />
            </VCol>
            <VCol cols="6">
              <VTextField
                v-model="newGroup.default_end_time"
                label="Eind tijd"
                type="time"
              />
            </VCol>
          </VRow>
        </VCardText>
        <VCardActions>
          <VSpacer />
          <VBtn @click="cancelGroupDialog">Annuleer</VBtn>
          <VBtn 
            color="primary" 
            @click="saveGroup"
            :loading="savingGroup"
          >
            {{ editingGroup ? 'Opslaan' : 'Maak Groep' }}
          </VBtn>
        </VCardActions>
      </VCard>
    </VDialog>
  </VContainer>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
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
const registrations = ref([])
const groups = ref([])
const selectedRegistrations = ref([])
const showCreateGroup = ref(false)
const trainers = ref([])
const locations = ref([])
const loadingTrainers = ref(false)
const loadingLocations = ref(false)
const savingGroup = ref(false)
const editingGroup = ref(null)

const weekDays = [
  { label: 'Ma', value: 'monday' },
  { label: 'Di', value: 'tuesday' },
  { label: 'Wo', value: 'wednesday' },
  { label: 'Do', value: 'thursday' },
  { label: 'Vr', value: 'friday' },
  { label: 'Za', value: 'saturday' },
  { label: 'Zo', value: 'sunday' },
]

const newGroup = ref({
  name: '',
  level: 'intermediate',
  max_participants: 4,
  schedule_days: [],
  trainer_id: null,
  location_id: null,
  default_start_time: '19:00',
  default_end_time: '20:00',
})

const levelOptions = [
  { title: 'Beginner', value: 'beginner' },
  { title: 'Gemiddeld', value: 'intermediate' },
  { title: 'Gevorderd', value: 'advanced' },
]

const unassignedRegistrations = computed(() => {
  return registrations.value.filter(r => !r.assigned_group_id)
})

const assignedCount = computed(() => {
  return registrations.value.filter(r => r.assigned_group_id).length
})

const unassignedCount = computed(() => {
  return registrations.value.filter(r => !r.assigned_group_id).length
})

const getLevelLabel = (level) => {
  const found = levelOptions.find(l => l.value === level)
  return found ? found.title : level
}

const getLevelColor = (level) => {
  const colors = {
    beginner: 'success',
    intermediate: 'warning',
    advanced: 'error'
  }
  return colors[level] || 'default'
}

const formatDays = (days) => {
  if (!days || days.length === 0) return 'Niet opgegeven'
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
    const packageResponse = await axios.get(`/lessons/packages/${packageId}`)
    lessonPackage.value = packageResponse.data.data
    registrations.value = packageResponse.data.data.registrations || []
    groups.value = packageResponse.data.data.groups || []
  } catch (error) {
    console.error('Error loading data:', error)
  }
}

const toggleRegistration = (regId) => {
  const index = selectedRegistrations.value.indexOf(regId)
  if (index > -1) {
    selectedRegistrations.value.splice(index, 1)
  } else {
    selectedRegistrations.value.push(regId)
  }
}

const resetGroupForm = () => {
  newGroup.value = {
    name: '',
    level: 'intermediate',
    max_participants: 4,
    schedule_days: [],
    trainer_id: null,
    location_id: null,
    default_start_time: '19:00',
    default_end_time: '20:00',
  }
  editingGroup.value = null
}

const cancelGroupDialog = () => {
  showCreateGroup.value = false
  resetGroupForm()
}

const saveGroup = async () => {
  if (!newGroup.value.name) {
    alert('Vul een naam in voor de groep')
    return
  }
  
  savingGroup.value = true
  try {
    if (editingGroup.value) {
      // Update existing group
      await axios.put(`/lessons/packages/${packageId}/groups/${editingGroup.value.id}`, {
        name: newGroup.value.name,
        level: newGroup.value.level,
        max_participants: newGroup.value.max_participants,
        schedule_days: newGroup.value.schedule_days,
        trainer_id: newGroup.value.trainer_id,
        location_id: newGroup.value.location_id,
        default_start_time: newGroup.value.default_start_time,
        default_end_time: newGroup.value.default_end_time,
      })
    } else {
      // Create new group
      await axios.post(`/lessons/packages/${packageId}/groups`, {
        name: newGroup.value.name,
        level: newGroup.value.level,
        max_participants: newGroup.value.max_participants,
        schedule_days: newGroup.value.schedule_days,
        trainer_id: newGroup.value.trainer_id,
        location_id: newGroup.value.location_id,
        default_start_time: newGroup.value.default_start_time,
        default_end_time: newGroup.value.default_end_time,
      })
    }
    
    resetGroupForm()
    showCreateGroup.value = false
    await loadData()
  } catch (error) {
    console.error('Error saving group:', error)
    alert('Fout bij opslaan groep: ' + (error.response?.data?.message || 'Onbekende fout'))
  } finally {
    savingGroup.value = false
  }
}

const editGroup = (group) => {
  editingGroup.value = group
  newGroup.value = {
    name: group.name,
    level: group.level,
    max_participants: group.max_participants,
    schedule_days: group.schedule_days || [],
    trainer_id: group.trainer_id,
    location_id: group.location_id,
    default_start_time: group.default_start_time || '19:00',
    default_end_time: group.default_end_time || '20:00',
  }
  showCreateGroup.value = true
}

const deleteGroup = async (groupId) => {
  if (!confirm('Weet je zeker dat je deze groep wilt verwijderen?')) {
    return
  }
  
  try {
    await axios.delete(`/lessons/packages/${packageId}/groups/${groupId}`)
    await loadData()
  } catch (error) {
    console.error('Error deleting group:', error)
    alert('Fout bij verwijderen groep: ' + (error.response?.data?.message || 'Onbekende fout'))
  }
}

const assignToGroup = async (groupId) => {
  if (selectedRegistrations.value.length === 0) {
    alert('Selecteer eerst leden om toe te wijzen')
    return
  }
  
  const group = groups.value.find(g => g.id === groupId)
  const availableSpots = group.max_participants - (group.registrations?.length || 0)
  
  if (selectedRegistrations.value.length > availableSpots) {
    alert(`Deze groep heeft maar ${availableSpots} plekken beschikbaar`)
    return
  }
  
  try {
    await axios.post(`/lessons/packages/${packageId}/groups/${groupId}/assign`, {
      registration_ids: selectedRegistrations.value
    })
    
    selectedRegistrations.value = []
    await loadData()
  } catch (error) {
    console.error('Error assigning to group:', error)
    alert(error.response?.data?.message || 'Fout bij toewijzen')
  }
}

const removeFromGroup = async (registrationId) => {
  if (!confirm('Weet je zeker dat je dit lid uit de groep wilt halen?')) {
    return
  }
  
  try {
    await axios.post(`/lessons/packages/${packageId}/remove-from-group`, {
      registration_id: registrationId
    })
    await loadData()
  } catch (error) {
    console.error('Error removing from group:', error)
    alert('Fout bij verwijderen uit groep: ' + (error.response?.data?.message || 'Onbekende fout'))
  }
}

const loadTrainers = async () => {
  loadingTrainers.value = true
  try {
    const response = await axios.get('/trainers')
    trainers.value = response.data.data || []
  } catch (error) {
    console.error('Error loading trainers:', error)
    // Fallback data voor development
    trainers.value = [
      { id: 1, name: 'Trainer Tom' },
      { id: 2, name: 'Trainer Sarah' },
    ]
  } finally {
    loadingTrainers.value = false
  }
}

const loadLocations = async () => {
  loadingLocations.value = true
  try {
    const response = await axios.get('/lessons/locations')
    locations.value = response.data.data || []
  } catch (error) {
    console.error('Error loading locations:', error)
  } finally {
    loadingLocations.value = false
  }
}

onMounted(() => {
  loadData()
  loadTrainers()
  loadLocations()
})
</script>

<style scoped>
.v-expansion-panel-text {
  padding-top: 1rem !important;
}
</style>
