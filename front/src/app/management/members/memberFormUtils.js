function isPersonalContact(contact) {
  return (
    String(contact?.relation || "").toLowerCase() === "self" ||
    String(contact?.name || "").toLowerCase() === "personal"
  );
}

/** Converts a member-details response record into the controlled edit form values. */
export function getMemberEditInitialValues(member) {
  if (!member) return null;

  const person = member.person || {};
  const fullName = person.full_name || "";
  const nameParts = fullName.trim().split(/\s+/).filter(Boolean);
  const contacts = Array.isArray(person.contacts)
    ? person.contacts
    : Array.isArray(member.contacts)
      ? member.contacts
      : [];
  const personalContact = contacts.find(isPersonalContact);
  const emergencyContact =
    contacts.find((contact) => !isPersonalContact(contact)) ||
    person.additional_contacts?.[0] ||
    member.additional_contacts?.[0] ||
    null;
  const dob = person.dob ?? member.dob ?? "";

  return {
    first_name: person.first_name || member.first_name || nameParts[0] || "",
    last_name: person.last_name || member.last_name || nameParts.slice(1).join(" ") || "",
    mobile_country_code:
      personalContact?.country_code ||
      person.mobile_country_code ||
      member.mobile_country_code ||
      "+963",
    mobile: personalContact?.phone_number || person.phone || person.mobile || member.mobile || "",
    gender: person.gender || member.gender || "male",
    dob: dob ? String(dob).split("T")[0] : "",
    age: person.age ?? member.age ?? "",
    branch_id: member.branch_id != null ? String(member.branch_id) : "",
    emergency_name: emergencyContact?.name || "",
    emergency_relation: emergencyContact?.relation || "Father",
    emergency_country_code: emergencyContact?.country_code || "+963",
    emergency_phone: emergencyContact?.phone_number || "",
    reason: "",
  };
}
