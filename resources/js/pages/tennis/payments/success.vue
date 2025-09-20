<template>
  <VContainer>
    <VRow>
      <VCol cols="12" md="6" offset-md="3">
        <VCard class="text-center pa-8">
          <VIcon 
            icon="tabler-circle-check" 
            size="80" 
            color="success"
            class="mb-4"
          />
          
          <h1 class="text-h4 mb-4">Betaling Succesvol!</h1>
          
          <p class="text-body-1 mb-6">
            Je inschrijving voor het evenement is bevestigd.
            Je ontvangt een bevestiging per email.
          </p>
          
          <VBtn 
            color="primary"
            :to="{ name: 'dashboard-tennis' }"
          >
            Terug naar Dashboard
          </VBtn>
          
          <VBtn 
            variant="outlined"
            :to="{ name: 'tennis-events-list' }"
            class="ml-2"
          >
            Naar Evenementen
          </VBtn>
        </VCard>
      </VCol>
    </VRow>
  </VContainer>
</template>

<script setup>
import { useRoute } from 'vue-router'

definePage({
  meta: {
    requiresAuth: false,
  },
})

const route = useRoute()
const registrationId = route.params.registrationId
</script>
