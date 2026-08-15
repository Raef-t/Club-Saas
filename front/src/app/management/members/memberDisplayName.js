export function getMemberDisplayName(member) {
  if (!member) return "";

  const person = member.person || {};
  const fullName =
    person.full_name ||
    `${person.first_name || member.first_name || ""} ${person.last_name || member.last_name || ""}`.trim();

  if (fullName) return fullName;
  return member.id == null ? "العضو" : `العضو #${member.id}`;
}
