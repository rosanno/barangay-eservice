export const blankMember = (isHead = false) => ({
  first_name: '', middle_name: '', last_name: '', suffix: '',
  relationship_to_head: isHead ? 'Head' : '', is_head: isHead,
  birthdate: '', sex: null, civil_status: null, education: '', occupation: '',
  is_pwd: false, is_solo_parent: false, is_registered_voter: false,
})

export const emptyForm = () => ({
  purok: '', street_address: '',
  housing_tenure: null, house_material: null, water_source: null, toilet_type: null,
  has_electricity: true, income_bracket: null, is_4ps: false,
  consent: false,
  members: [blankMember(true)],
})

export function fillForm(form, h) {
  Object.assign(form, {
    purok: h.purok, street_address: h.street_address,
    housing_tenure: h.housing_tenure, house_material: h.house_material,
    water_source: h.water_source, toilet_type: h.toilet_type,
    has_electricity: h.has_electricity, income_bracket: h.income_bracket, is_4ps: h.is_4ps,
    consent: false,
    members: h.members.map(({ id, full_name, age, ...m }) => ({
      ...blankMember(),
      ...m,
      birthdate: m.birthdate ? String(m.birthdate).slice(0, 10) : '',
      middle_name: m.middle_name ?? '',
      suffix: m.suffix ?? '',
      education: m.education ?? '',
      occupation: m.occupation ?? '',
    })),
  })
}