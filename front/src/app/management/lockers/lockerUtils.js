import { formatLocalizedName, getBranchesArray } from "../../../lib/utils";
import { LOCKER_OCCUPIED_STATUSES } from "./lockerConstants";

/**
 * Extracts a collection from the supported backend response shapes.
 */
export function getLockerCollection(response) {
  if (Array.isArray(response?.data?.lockers)) return response.data.lockers;
  if (Array.isArray(response?.data?.data?.lockers)) return response.data.data.lockers;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.lockers)) return response.lockers;
  if (Array.isArray(response)) return response;
  return [];
}

/**
 * Extracts a summary object from the supported backend response shapes.
 */
export function getLockerSummary(response) {
  if (response?.data?.summary) return response.data.summary;
  if (response?.data?.data?.summary) return response.data.data.summary;
  if (response?.summary) return response.summary;
  return null;
}

/**
 * Extracts a single locker from the supported backend response shapes.
 */
export function getLockerRecord(response) {
  return response?.data?.data || response?.data || response || null;
}

/**
 * Reports whether a locker currently belongs to an active holder.
 */
export function isLockerOccupied(locker) {
  return LOCKER_OCCUPIED_STATUSES.includes(locker?.status) || Boolean(locker?.holder_id);
}

/**
 * Filters lockers by number, branch, and aggregate UI status.
 */
export function filterLockers(lockers, { search = "", branch = "all", status = "all" } = {}) {
  const normalizedSearch = search.trim().toLowerCase();

  return lockers.filter((locker) => {
    const matchesSearch =
      !normalizedSearch ||
      String(locker.locker_number || "")
        .toLowerCase()
        .includes(normalizedSearch);
    const matchesBranch = branch === "all" || String(locker.branch_id) === String(branch);
    const matchesStatus =
      status === "all" ||
      (status === "occupied" ? isLockerOccupied(locker) : locker.status === status);

    return matchesSearch && matchesBranch && matchesStatus;
  });
}

/**
 * Creates the exact query params supported by the locker list endpoint.
 */
export function createLockerQueryParams(branchFilter, statusFilter) {
  return {
    branch_id: branchFilter !== "all" ? String(branchFilter) : undefined,
    status: statusFilter !== "all" && statusFilter !== "occupied" ? statusFilter : undefined,
  };
}

/**
 * Builds localized branch dropdown options.
 */
export function createLockerBranchOptions(response, includeAll = false) {
  const options = getBranchesArray(response).map((branch) => ({
    value: String(branch.id),
    label: formatLocalizedName(branch.name),
  }));

  return includeAll ? [{ value: "all", label: "كل الفروع" }, ...options] : options;
}

/**
 * Resolves the branch label shown on a locker card.
 */
export function getLockerBranchName(locker, branches) {
  const branch = branches.find((item) => String(item.id) === String(locker.branch_id));
  return branch ? formatLocalizedName(branch.name) : `فرع #${locker.branch_id || "-"}`;
}

/**
 * Builds member dropdown options and a lookup map from backend data.
 */
export function createLockerMemberOptions(response) {
  return getLockerCollection(response).map((member) => {
    const person = member.person || {};
    const fullName =
      person.full_name ||
      `${member.first_name || ""} ${member.last_name || ""}`.trim() ||
      `عضو #${member.id}`;

    return {
      value: String(member.id),
      label: fullName,
    };
  });
}

/**
 * Builds coach dropdown options while keeping the backend coach identifier as the value.
 */
export function createLockerCoachOptions(response) {
  return getLockerCollection(response).map((coach) => {
    const person = coach.person || {};
    const fullName =
      person.full_name ||
      `${person.first_name || coach.first_name || ""} ${
        person.last_name || coach.last_name || ""
      }`.trim() ||
      `كوتش #${coach.id}`;

    return {
      value: String(coach.id),
      label: fullName,
    };
  });
}

/**
 * Builds staff dropdown options while keeping the backend identifier as the value.
 */
export function createLockerStaffOptions(response) {
  return getLockerCollection(response).map((staff) => {
    const person = staff.person || {};
    const fullName =
      person.full_name ||
      `${person.first_name || staff.first_name || ""} ${
        person.last_name || staff.last_name || ""
      }`.trim() ||
      `موظف #${staff.id}`;

    return {
      value: String(staff.id),
      label: fullName,
    };
  });
}

/**
 * Resolves the current locker holder label without replacing its status label.
 */
export function getLockerHolderLabel(
  locker,
  memberOptions = [],
  coachOptions = [],
  staffOptions = [],
) {
  if (!isLockerOccupied(locker)) return "";
  if (locker.holder_name) return locker.holder_name;

  if (locker.holder_type === "member" && locker.holder_id) {
    return (
      memberOptions.find((member) => member.value === String(locker.holder_id))?.label ||
      `لاعب #${locker.holder_id}`
    );
  }

  if (locker.holder_type === "staff" && locker.holder_id) {
    return (
      staffOptions.find((staff) => staff.value === String(locker.holder_id))?.label ||
      `موظف #${locker.holder_id}`
    );
  }

  if (locker.holder_type === "coach" && locker.holder_id) {
    return (
      coachOptions.find((coach) => coach.value === String(locker.holder_id))?.label ||
      `كوتش #${locker.holder_id}`
    );
  }

  if (locker.holder_type === "guest" && locker.holder_id) {
    return `زائر #${locker.holder_id}`;
  }

  return locker.holder_id ? `مستفيد #${locker.holder_id}` : "";
}

/**
 * Converts Zod issues into the field-error object used by locker forms.
 */
export function getLockerValidationErrors(validationError) {
  return Object.fromEntries(
    validationError.issues.map((issue) => [issue.path.join("_"), issue.message]),
  );
}

/**
 * Creates the form state used when editing an existing locker.
 */
export function createLockerUpdateInitialValues(locker) {
  return {
    locker_number: String(locker?.locker_number || ""),
    key_number: locker?.key_number ? String(locker.key_number) : "",
    status: locker?.status || "available",
    holder_type: locker?.holder_type || "",
    holder_id: locker?.holder_id ? String(locker.holder_id) : "",
    holder_name: locker?.holder_name || "",
  };
}

/**
 * Creates a normalized locker update payload from editable form values.
 */
export function createLockerUpdatePayload(form) {
  const payload = {
    locker_number: form.locker_number.trim(),
    key_number: form.key_number?.trim() || null,
    status: form.status,
  };

  if (LOCKER_OCCUPIED_STATUSES.includes(form.status) && form.holder_type) {
    payload.holder_type = form.holder_type;
    if (form.holder_id) payload.holder_id = Number(form.holder_id);
    if (form.holder_name?.trim()) {
      payload.holder_name = form.holder_name.trim();
    }
  }

  return payload;
}

/**
 * Creates a normalized reservation payload while omitting empty optional fields.
 */
export function createLockerReservationPayload(form) {
  const payload = {
    reservation_type: form.reservation_type,
    holder_type: form.holder_type,
    holder_id: Number(form.holder_id),
    start_date: form.start_date,
  };

  if (form.reservation_type === "rental" && form.price !== "") {
    payload.price = Number(form.price);
  }
  if (form.end_date) payload.end_date = form.end_date;

  return payload;
}
