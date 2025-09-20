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
            <VCardTitle>Inschrijvingen</VCardTitle>
          </VCardItem>
          <VCardText>
            <VList>
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
                  />
                </template>
                
                <VListItemTitle>{{ reg.user.name }}</VListItemTitle>
                <VListItemSubtitle>
                  <div>Niveau: {{ reg.level || 'Niet opgegeven' }}</div>
                  <div>Beschikbaar: {{ reg.available_days?.join(', ') || 'Niet opgegeven' }}</div>
                  <div v-if="reg.preferred_partners?.length">
                    Samen met: {{ reg.preferred_partners.join(', ') }}
                  </div>
                </VListItemSubtitle>
              </VListItem>
            </VList>
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
            <VExpansionPanels>
              <VExpansionPanel
                v-for="group in groups"
                :key="group.id"
              >
                <VExpansionPanelTitle>
                  {{ group.name }} ({{ group.registrations?.length || 0 }}/{{ group.max_participants }})
                </VExpansionPanelTitle>
                <VExpansionPanelText>
                  <p>Trainer: {{ group.trainer?.name || 'Niet toegewezen' }}</p>
                  <p>Niveau: {{ group.level }}</p>
                  <p>Dagen: {{ group.schedule_days?.join(', ') }}</p>
                  
                  <VDivider class="my-3" />
                  
                  <p class="font-weight-bold mb-2">Leden:</p>
                  <VList dense>
                    <VListItem
                      v-for="reg in group.registrations"
                      :key="reg.id"
                    >
                      {{ reg.user.name }}
                    </VListItem>
                  </VList>
                  
                  <VBtn
                    v-if="selectedRegistrations.length > 0"
                    size="small"
                    color="primary"
                    class="mt-3"
                    @click="assignToGroup(group.id)"
                  >
                    Voeg geselecteerde toe
                  </VBtn>
                </VExpansionPanelText>
              </VExpansionPanel>
            </VExpansionPanels>
          </VCardText>
        </VCard>
      </VCol>
    </VRow>

    <!-- Create Group Dialog -->
    <VDialog v-model="showCreateGroup" max-width="600">
    <VCard>
        <VCardTitle>Nieuwe Groep</VCardTitle>
        <VCardText>
        <VTextField
            v-model="newGroup.name"
            label="Naam"
            class="mb-3"
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
        />
        
        <VSelect
            v-model="newGroup.trainer_id"
            label="Trainer"
            :items="trainers"
            item-title="name"
            item-value="id"
            clearable
            class="mb-3"
        />
        
        <VSelect
            v-model="newGroup.location_id"
            label="Locatie"
            :items="locations"
            item-title="name"
            item-value="id"
            clearable
            class="mb-3"
        />
        
        <p class="text-body-1 mb-2">Lesdagen:</p>
        <VCheckbox
            v-for="day in weekDays"
            :key="day.value"
            v-model="newGroup.schedule_days"
            :label="day.label"
            :value="day.value"
            inline
        />
        
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
        <VBtn @click="showCreateGroup = false">Annuleer</VBtn>
        <VBtn color="primary" @click="createGroup">Maak Groep</VBtn>
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

const weekDays = [
  { label: 'Maandag', value: 'monday' },
  { label: 'Dinsdag', value: 'tuesday' },
  { label: 'Woensdag', value: 'wednesday' },
  { label: 'Donderdag', value: 'thursday' },
  { label: 'Vrijdag', value: 'friday' },
  { label: 'Zaterdag', value: 'saturday' },
  { label: 'Zondag', value: 'sunday' },
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

const loadData = async () => {
  try {
    // Load package details
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

const createGroup = async () => {
  try {
    const response = await axios.post(`/lessons/packages/${packageId}/groups`, {
      name: newGroup.value.name,
      level: newGroup.value.level,
      max_participants: newGroup.value.max_participants,
      schedule_days: newGroup.value.schedule_days,
      trainer_id: newGroup.value.trainer_id,
      location_id: newGroup.value.location_id,
      default_start_time: newGroup.value.default_start_time,
      default_end_time: newGroup.value.default_end_time,
    })
    
    // Reset form
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
    
    showCreateGroup.value = false
    await loadData()
  } catch (error) {
    console.error('Error creating group:', error)
    alert('Fout bij aanmaken groep')
  }
}

const assignToGroup = async (groupId) => {
  if (selectedRegistrations.value.length === 0) {
    alert('Selecteer eerst leden om toe te wijzen')
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
  try {
    await axios.post(`/lessons/packages/${packageId}/remove-from-group`, {
      registration_id: registrationId
    })
    await loadData()
  } catch (error) {
    console.error('Error removing from group:', error)
  }
}

const loadTrainers = async () => {
  try {
    const response = await axios.get('/members?role=trainer')
    trainers.value = response.data.members || []
  } catch (error) {
    console.error('Error loading trainers:', error)
    // Gebruik dummy data voor nu
    trainers.value = [
      { id: 1, name: 'Trainer Tom' },
      { id: 2, name: 'Trainer Sarah' },
    ]
  }
}

const loadLocations = async () => {
  try {
    const response = await axios.get('/lessons/locations')
    locations.value = response.data.data || []
  } catch (error) {
    console.error('Error loading locations:', error)
  }
}

onMounted(() => {
  loadData()
  loadTrainers()
  loadLocations()
})
</script>
