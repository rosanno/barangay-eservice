export const STATUS = {
  pending:    { label: 'Pending',    color: '#B7791F', bg: '#FEF3C7' },
  processing: { label: 'Processing', color: '#1D4ED8', bg: '#DBEAFE' },
  ready:      { label: 'Ready for pickup', color: '#047857', bg: '#D1FAE5' },
  released:   { label: 'Released',   color: '#374151', bg: '#E5E7EB' },
  rejected:   { label: 'Rejected',   color: '#B91C1C', bg: '#FEE2E2' },
  cancelled:  { label: 'Cancelled',  color: '#6B7280', bg: '#F3F4F6' },
}

// Order used for the progress tracker on the detail page.
export const STATUS_STEPS = ['pending', 'processing', 'ready', 'released']

export const DOCUMENT_TYPES = [
  { value: 'barangay_clearance',     title: 'Barangay Clearance',       fee: 50 },
  { value: 'certificate_residency',  title: 'Certificate of Residency', fee: 30 },
  { value: 'certificate_indigency',  title: 'Certificate of Indigency', fee: 0 },
  { value: 'business_clearance',     title: 'Business Clearance',       fee: 200 },
]

export const PURPOSES = [
  'Employment', 'School enrollment', 'Scholarship', 'Travel / Passport',
  'Business permit', 'Medical assistance', 'Loan application', 'Other',
]

export const formatDate = (d) =>
  d ? new Date(d).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' }) : '—'