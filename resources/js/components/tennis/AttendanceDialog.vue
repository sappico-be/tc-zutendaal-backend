<template>
  <VDialog v-model="show" max-width="800" persistent>
    <VCard>
      <VCardTitle class="d-flex justify-space-between align-center">
        <span>Aanwezigheid Registreren</span>
        <VBtn icon="tabler-x" variant="text" @click="close" />
      </VCardTitle>
      
      <VCardText>
        <!-- Lesson Info -->
        <VAlert type="info" class="mb-4">
          <div><strong>Les:</strong> {{ lesson?.group?.name }}</div>
          <div><strong>Datum:</strong> {{ formatDate(lesson?.lesson_date) }}</div>
          <div><strong>Tijd:</strong> {{ lesson?.start_time }} - {{ lesson?.end_time }}</div>
          <div><strong>Locatie:</strong> {{ lesson?.location?.name || '-' }}</div>
        </VAlert>

        <!-- Quick Actions -->
        <div class="d-flex gap-2 mb-4">
          <VBtn 
            size="small" 
            color="success"
            @click="markAllPresent"
          >
            Iedereen Aanwezig
          </VBtn>
          <VBtn 
            size="small" 
            color="error"
            @click="markAllAbsent"
          >
            Iedereen Afwezig
          </VBtn>
        </div>

        <!-- Attendance Table -->
        <VTable>
          <thead>
            <tr>
              <th>Naam</th>
              <th>Status</th>
              <th>Notities</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(attendance, index) in attendances" :key="attendance.user_id">
              <td>
                <div class="d-flex align-center">
                  <VAvatar size="32" class="me-2">
                    <span>{{ getInitials(attendance.user_name) }}</span>
                  </VAvatar>
                  <div>
                    <div>{{ attendance.user_name }}</div>
                    <div class="text-caption text-disabled">{{ attendance.user_email }}</div>
                  </div>
                </div>
              </td>
              <td>
                <VBtnToggle
                  v-model="attendance.status"
                  mandatory
                  variant="outlined"
                  divided
                  density="compact"
                >
                  <VBtn 
                    value="present" 
                    color="success"
                    size="small"
                  >
                    <VIcon size="18">tabler-check</VIcon>
                  </VBtn>
                  <VBtn 
                    value="late" 
                    color="warning"
                    size="small"
                  >
                    <VIcon size="18">tabler-clock</VIcon>
                  </VBtn>
                  <VBtn 
                    value="excused" 
                    color="info"
                    size="small"
                  >
                    <VIcon size="18">tabler-mail</VIcon>
                  </VBtn>
                  <VBtn 
                    value="absent" 
                    color="error"
                    size="small"
                  >
                    <VIcon size="18">tabler-x</VIcon>
                  </VBtn>
                </VBtnToggle>
              </td>
              <td>
                <VTextField
                  v-model="attendance.notes"
                  density="compact"
                  variant="outlined"
                  hide-details
                  placeholder="Optionele notitie..."
                />
              </td>
            </tr>
          </tbody>
        </VTable>

        <!-- Statistics -->
        <VRow class="mt-4">
          <VCol cols="3">
            <VCard variant="tonal" color="success">
              <VCardText class="text-center">
                <div class="text-h5">{{ presentCount }}</div>
                <div class="text-caption">Aanwezig</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard variant="tonal" color="warning">
              <VCardText class="text-center">
                <div class="text-h5">{{ lateCount }}</div>
                <div class="text-caption">Te laat</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard variant="tonal" color="info">
              <VCardText class="text-center">
                <div class="text-h5">{{ excusedCount }}</div>
                <div class="text-caption">Afgemeld</div>
              </VCardText>
            </VCard>
          </VCol>
          <VCol cols="3">
            <VCard variant="tonal" color="error">
              <VCardText class="text-center">
                <div class="text-h5">{{ absentCount }}</div>
                <div class="text-caption">Afwezig</div>
              </VCardText>
            </VCard>
          </VCol>
        </VRow>
      </VCardText>

      <VCardActions>
        <VSpacer />
        <VBtn variant="outlined" @click="close">Annuleren</VBtn>
        <VBtn 
          color="primary" 
          @click="saveAttendance"
          :loading="saving"
        >
          Opslaan
        </VBtn>
      </VCardActions>
    </VCard>
  </VDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from '@/plugins/axios'

const props = defineProps({
  modelValue: Boolean,
  lesson: Object,
  packageId: String,
})

const emit = defineEmits(['update:modelValue', 'saved'])

const show = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

const attendances = ref([])
const saving = ref(false)

const presentCount = computed(() => attendances.value.filter(a => a.status === 'present').length)
const lateCount = computed(() => attendances.value.filter(a => a.status === 'late').length)
const excusedCount = computed(() => attendances.value.filter(a => a.status === 'excused').length)
const absentCount = computed(() => attendances.value.filter(a => a.status === 'absent').length)

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('nl-BE')
}

const loadAttendance = async () => {
  if (!props.lesson) return
  
  try {
    const response = await axios.get(
      `/lessons/packages/${props.packageId}/groups/${props.lesson.lesson_group_id}/schedule/${props.lesson.id}/attendance`
    )
    
    attendances.value = response.data.data.attendances.map(a => ({
      ...a,
      status: a.status || 'absent' // Default to absent if no status
    }))
  } catch (error) {
    console.error('Error loading attendance:', error)
  }
}

const markAllPresent = () => {
  attendances.value.forEach(a => {
    a.status = 'present'
  })
}

const markAllAbsent = () => {
  attendances.value.forEach(a => {
    a.status = 'absent'
  })
}

const saveAttendance = async () => {
  saving.value = true
  
  try {
    await axios.post(
      `/lessons/packages/${props.packageId}/groups/${props.lesson.lesson_group_id}/schedule/${props.lesson.id}/attendance`,
      {
        attendances: attendances.value
      }
    )
    
    emit('saved')
    close()
  } catch (error) {
    console.error('Error saving attendance:', error)
    alert('Fout bij opslaan aanwezigheid')
  } finally {
    saving.value = false
  }
}

const close = () => {
  show.value = false
}

watch(() => props.modelValue, (newVal) => {
  if (newVal) {
    loadAttendance()
  }
})
</script>

<style scoped>
:deep(.v-btn-toggle) {
  height: 32px;
}
</style>
