function normalizeStat(value) {
  const number = Number(value);
  return Number.isFinite(number) ? number : 0;
}

/** Reads aggregate member counts returned by the members endpoint. */
export function getMemberStats(response) {
  const stats = response?.stats || {};

  return {
    totalMembers: normalizeStat(stats.total_members),
    activeMembers: normalizeStat(stats.active_members),
    maleMembers: normalizeStat(stats.male_members),
    femaleMembers: normalizeStat(stats.female_members),
  };
}
