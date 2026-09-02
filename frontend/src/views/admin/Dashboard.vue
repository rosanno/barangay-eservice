<template>
  <div>
    <p class="text-body-1 mb-8" style="color: #5c5c5c">
      Overview of today's requests and activity.
    </p>

    <v-row>
      <v-col v-for="stat in stats" :key="stat.label" cols="12" sm="6" lg="3">
        <v-card variant="flat" style="border: 1px solid #e6e1d3; background: #ffffff">
          <v-card-text class="pa-6">
            <div class="d-flex align-center justify-space-between mb-4">
              <v-avatar size="40" color="primary" variant="tonal" rounded="sm">
                <v-icon :icon="stat.icon" color="#0b1b32" />
              </v-avatar>
            </div>
            <div class="text-h4 auth-layout__form-title" style="color: #1c1c1c">
              {{ stat.value }}
            </div>
            <div class="text-body-2" style="color: #5c5c5c">{{ stat.label }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-row class="mt-2">
      <v-col cols="12" lg="7">
        <v-card variant="flat" style="border: 1px solid #e6e1d3; background: #ffffff">
          <v-card-title class="auth-layout__form-title pa-6 pb-0" style="color: #1c1c1c">
            Recent requests
          </v-card-title>
          <v-card-text class="pa-6">
            <v-table>
              <thead>
                <tr>
                  <th class="text-body-2" style="color: #8a7f5f">Resident</th>
                  <th class="text-body-2" style="color: #8a7f5f">Type</th>
                  <th class="text-body-2" style="color: #8a7f5f">Status</th>
                  <th class="text-body-2" style="color: #8a7f5f">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="req in recentRequests" :key="req.id">
                  <td>{{ req.resident }}</td>
                  <td>{{ req.type }}</td>
                  <td>
                    <v-chip size="small" :color="statusColor(req.status)" variant="tonal">
                      {{ req.status }}
                    </v-chip>
                  </td>
                  <td style="color: #5c5c5c">{{ req.date }}</td>
                </tr>
              </tbody>
            </v-table>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" lg="5">
        <v-card variant="flat" style="border: 1px solid #e6e1d3; background: #ffffff">
          <v-card-title class="auth-layout__form-title pa-6 pb-0" style="color: #1c1c1c">
            Today's appointments
          </v-card-title>
          <v-card-text class="pa-6">
            <div
              v-for="appt in appointments"
              :key="appt.id"
              class="d-flex justify-space-between align-center py-3"
              style="border-bottom: 1px solid #f0ede3"
            >
              <div>
                <div class="text-body-2 font-weight-medium" style="color: #1c1c1c">
                  {{ appt.resident }}
                </div>
                <div class="text-caption" style="color: #8a7f5f">{{ appt.purpose }}</div>
              </div>
              <div class="text-body-2" style="color: #5c5c5c">{{ appt.time }}</div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
// Placeholder data — wire these to your ClearanceController / AppointmentController
// endpoints once they're built; shape is kept close to what those API responses
// will likely look like.
const stats = [
  { label: 'Pending requests', value: '12', icon: 'mdi-file-clock-outline' },
  { label: "Today's appointments", value: '5', icon: 'mdi-calendar-check-outline' },
  { label: 'Registered residents', value: '1,204', icon: 'mdi-account-group-outline' },
  { label: 'Issued this month', value: '87', icon: 'mdi-check-decagram-outline' },
]

const recentRequests = [
  { id: 1, resident: 'Juan Dela Cruz', type: 'Barangay Clearance', status: 'Pending', date: 'Sep 1' },
  { id: 2, resident: 'Maria Santos', type: 'Certificate of Residency', status: 'Approved', date: 'Sep 1' },
  { id: 3, resident: 'Pedro Reyes', type: 'Business Permit Endorsement', status: 'Pending', date: 'Aug 31' },
]

const appointments = [
  { id: 1, resident: 'Ana Lopez', purpose: 'ID verification', time: '9:00 AM' },
  { id: 2, resident: 'Carlo Ramos', purpose: 'Clearance follow-up', time: '10:30 AM' },
  { id: 3, resident: 'Liza Torres', purpose: 'Document release', time: '2:00 PM' },
]

function statusColor(status) {
  return { Pending: 'warning', Approved: 'success', Rejected: 'error' }[status] || 'default'
}
</script>
