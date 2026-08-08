export const WORK_STATUSES = ["active", "suspended", "on_leave"];

export const WORK_STATUS_LABELS = {
  active: "نشط",
  suspended: "موقوف",
  on_leave: "إجازة",
};

export const WORK_STATUS_OPTIONS = WORK_STATUSES.map((value) => ({
  value,
  label: WORK_STATUS_LABELS[value],
}));

export const WORK_STATUS_CLASSES = {
  active: "bg-app-green/10 text-app-green",
  suspended: "bg-app-red/10 text-app-red",
  on_leave: "bg-app-yellow/10 text-app-yellow",
};

/**
 * Resolves the new work status while keeping old API records displayable.
 */
export function resolveWorkStatus(recordOrStatus) {
  const explicitStatus =
    typeof recordOrStatus === "string" ? recordOrStatus : recordOrStatus?.work_status;

  if (WORK_STATUSES.includes(explicitStatus)) return explicitStatus;
  if (typeof recordOrStatus === "object" && recordOrStatus?.is_active === false) {
    return "suspended";
  }

  return "active";
}

export function getWorkStatusMeta(recordOrStatus) {
  const value = resolveWorkStatus(recordOrStatus);
  return {
    value,
    label: WORK_STATUS_LABELS[value],
    className: WORK_STATUS_CLASSES[value],
  };
}
