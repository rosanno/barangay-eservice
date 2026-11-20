<script setup>
import { blankMember } from '@/utils/householdForm'

// `form` is mutated in place by the parent's reactive object
const props = defineProps({
  form: { type: Object, required: true },
  errors: { type: Object, default: () => ({}) },
})

const opts = {
  tenure:   [['owned', 'Owned'], ['rented', 'Rented'], ['shared', 'Shared with relatives'], ['informal_settler', 'Informal settler']],
  material: [['concrete', 'Concrete'], ['semi_concrete', 'Semi-concrete'], ['light_materials', 'Light materials']],
  water:    [['piped', 'Piped / Level 3'], ['deep_well', 'Deep well'], ['spring', 'Spring'], ['delivered', 'Delivered / Refilling station'], ['other', 'Other']],
  toilet:   [['water_sealed', 'Water-sealed'], ['pit', 'Pit latrine'], ['shared', 'Shared'], ['none', 'None']],
  income:   [['below_10k', 'Below ₱10,000'], ['10k_20k', '₱10,000 – ₱20,000'], ['20k_40k', '₱20,000 – ₱40,000'], ['above_40k', 'Above ₱40,000']],
  sex:      [['male', 'Male'], ['female', 'Female']],
  civil:    [['single', 'Single'], ['married', 'Married'], ['widowed', 'Widowed'], ['separated', 'Separated'], ['live_in', 'Live-in']],
}
const toItems = (pairs) => pairs.map(([value, title]) => ({ value, title }))
const err = (field) => props.errors[field]?.[0]

const addMember = () => props.form.members.push(blankMember())
const removeMember = (i) => props.form.members.splice(i, 1)
function makeHead(i) {
  props.form.members.forEach((m, idx) => {
    m.is_head = idx === i
    if (idx === i) m.relationship_to_head = 'Head'
    else if (m.relationship_to_head === 'Head') m.relationship_to_head = ''
  })
}
</script>

<template>
  <div>
    <!-- Household information -->
    <section class="card">
      <p class="section-label">Household information</p>
      <v-row dense>
        <v-col cols="12" sm="4"><v-text-field v-model="form.purok" label="Purok / Zone" variant="outlined" density="compact" :error-messages="err('purok')" /></v-col>
        <v-col cols="12" sm="8"><v-text-field v-model="form.street_address" label="Street / House No." variant="outlined" density="compact" :error-messages="err('street_address')" /></v-col>
        <v-col cols="12" sm="6"><v-select v-model="form.housing_tenure" :items="toItems(opts.tenure)" label="Housing tenure" variant="outlined" density="compact" :error-messages="err('housing_tenure')" /></v-col>
        <v-col cols="12" sm="6"><v-select v-model="form.house_material" :items="toItems(opts.material)" label="House material" variant="outlined" density="compact" :error-messages="err('house_material')" /></v-col>
        <v-col cols="12" sm="6"><v-select v-model="form.water_source" :items="toItems(opts.water)" label="Water source" variant="outlined" density="compact" :error-messages="err('water_source')" /></v-col>
        <v-col cols="12" sm="6"><v-select v-model="form.toilet_type" :items="toItems(opts.toilet)" label="Toilet facility" variant="outlined" density="compact" :error-messages="err('toilet_type')" /></v-col>
        <v-col cols="12" sm="6"><v-select v-model="form.income_bracket" :items="toItems(opts.income)" label="Monthly household income (optional)" variant="outlined" density="compact" clearable /></v-col>
        <v-col cols="12" sm="6" class="d-flex flex-column justify-center">
          <v-switch v-model="form.has_electricity" label="Has electricity" color="#0f1e3d" hide-details density="compact" inset />
          <v-switch v-model="form.is_4ps" label="4Ps beneficiary" color="#0f1e3d" hide-details density="compact" inset />
        </v-col>
      </v-row>
    </section>

    <!-- Members -->
    <div class="d-flex align-center justify-space-between mb-3">
      <p class="section-label" style="margin: 0">
        Household members ({{ form.members.length }})
      </p>
      <v-btn
        size="small"
        variant="tonal"
        style="text-transform: none"
        prepend-icon="mdi-plus"
        :disabled="form.members.length >= 30"
        @click="addMember"
      >
        Add member
      </v-btn>
    </div>

    <section v-for="(m, i) in form.members" :key="i" class="card member" :class="{ 'member--head': m.is_head }">
      <div class="d-flex align-center justify-space-between mb-3">
        <div class="d-flex align-center ga-2">
          <span style="font-size: 12.5px; font-weight: 600; color: #1a1a1a">Member {{ i + 1 }}</span>
          <span v-if="m.is_head" class="chip-head">Head</span>
          <button v-else type="button" class="link-btn" @click="makeHead(i)">Set as head</button>
        </div>
        <v-btn
          v-if="form.members.length > 1 && !m.is_head"
          icon="mdi-delete-outline"
          variant="text"
          density="comfortable"
          class="action-btn"
          aria-label="Remove member"
          @click="removeMember(i)"
        />
      </div>

      <v-row dense>
        <v-col cols="12" sm="4"><v-text-field v-model="m.first_name" label="First name" variant="outlined" density="compact" :error-messages="err(`members.${i}.first_name`)" /></v-col>
        <v-col cols="12" sm="3"><v-text-field v-model="m.middle_name" label="Middle name" variant="outlined" density="compact" /></v-col>
        <v-col cols="8" sm="3"><v-text-field v-model="m.last_name" label="Last name" variant="outlined" density="compact" :error-messages="err(`members.${i}.last_name`)" /></v-col>
        <v-col cols="4" sm="2"><v-text-field v-model="m.suffix" label="Suffix" variant="outlined" density="compact" /></v-col>

        <v-col cols="12" sm="4"><v-text-field v-model="m.relationship_to_head" label="Relationship to head" variant="outlined" density="compact" :readonly="m.is_head" :error-messages="err(`members.${i}.relationship_to_head`)" /></v-col>
        <v-col cols="12" sm="3"><v-text-field v-model="m.birthdate" type="date" label="Birthdate" variant="outlined" density="compact" :error-messages="err(`members.${i}.birthdate`)" /></v-col>
        <v-col cols="6" sm="2"><v-select v-model="m.sex" :items="toItems(opts.sex)" label="Sex" variant="outlined" density="compact" :error-messages="err(`members.${i}.sex`)" /></v-col>
        <v-col cols="6" sm="3"><v-select v-model="m.civil_status" :items="toItems(opts.civil)" label="Civil status" variant="outlined" density="compact" :error-messages="err(`members.${i}.civil_status`)" /></v-col>

        <v-col cols="12" sm="6"><v-text-field v-model="m.education" label="Highest education (optional)" variant="outlined" density="compact" /></v-col>
        <v-col cols="12" sm="6"><v-text-field v-model="m.occupation" label="Occupation (optional)" variant="outlined" density="compact" /></v-col>

        <v-col cols="12" class="d-flex flex-wrap ga-4">
          <v-checkbox v-model="m.is_pwd" label="PWD" color="#0f1e3d" hide-details density="compact" />
          <v-checkbox v-model="m.is_solo_parent" label="Solo parent" color="#0f1e3d" hide-details density="compact" />
          <v-checkbox v-model="m.is_registered_voter" label="Registered voter" color="#0f1e3d" hide-details density="compact" />
        </v-col>
      </v-row>
    </section>
  </div>
</template>

<style scoped>
.card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 16px; }
.section-label { font-size: 11px; font-weight: 600; color: #999; text-transform: uppercase; letter-spacing: .04em; margin: 0 0 14px; }
.member--head { border-left: 3px solid #f5a623; }
.chip-head { font-size: 10px; font-weight: 600; border-radius: 20px; padding: 2px 10px; background: #e8f0fe; color: #1a5fd8; }
.link-btn { border: 0; background: transparent; font: inherit; font-size: 12px; font-weight: 600; color: #0f1e3d; cursor: pointer; padding: 2px 6px; border-radius: 6px; }
.link-btn:hover { background: #f3f1ec; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; color: #8a8f98; }
.action-btn:hover { background: #fdecea; color: #c0392b; }
</style>