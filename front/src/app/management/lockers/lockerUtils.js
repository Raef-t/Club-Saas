import { formatLocalizedName, getBranchesArray } from "../../../lib/utils";
import { fromIsoDate, toIsoDate } from "@/components/forms/datePickerUtils";
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
 * Reads a person's display name from the relation shapes returned by the API.
 */
function getLockerPersonName(record) {
  if (!record || typeof record !== "object") return "";

  const person = record.person && typeof record.person === "object" ? record.person : {};
  const directName =
    record.holder_name ||
    record.full_name ||
    record.display_name ||
    (typeof record.name === "string" ? record.name : "") ||
    person.full_name ||
    person.display_name ||
    (typeof person.name === "string" ? person.name : "");

  if (directName) return directName;

  return `${person.first_name || record.first_name || ""} ${
    person.last_name || record.last_name || ""
  }`.trim();
}

/**
 * Normalizes the current holder whether it is returned on the locker itself or
 * nested inside the active reservation relation.
 */
export function getLockerCurrentReservation(locker) {
  return (
    locker?.current_reservation ||
    locker?.active_reservation ||
    locker?.reservation ||
    locker?.locker_reservation ||
    null
  );
}

export function getLockerReservationEndDate(locker) {
  const reservation = getLockerCurrentReservation(locker);
  return reservation?.end_date || locker?.end_date || locker?.reservation_end_date || "";
}

/**
 * Adds one calendar month while clamping dates at the end of shorter months.
 * For example, January 31 becomes February 28 (or 29 in a leap year).
 */
export function getLockerRentalEndDate(startDate) {
  const start = fromIsoDate(startDate);
  if (!start) return "";

  const targetYear = start.getMonth() === 11 ? start.getFullYear() + 1 : start.getFullYear();
  const targetMonth = (start.getMonth() + 1) % 12;
  const lastDayOfTargetMonth = new Date(targetYear, targetMonth + 1, 0).getDate();
  const end = new Date(targetYear, targetMonth, Math.min(start.getDate(), lastDayOfTargetMonth));

  return toIsoDate(end);
}

export function isLockerEarlyRelease(locker, now = new Date()) {
  const endDate = new Date(getLockerReservationEndDate(locker));
  const comparisonDate = now instanceof Date ? now : new Date(now);

  return (
    Number.isFinite(endDate.getTime()) &&
    Number.isFinite(comparisonDate.getTime()) &&
    endDate.getTime() > comparisonDate.getTime()
  );
}

/**
 * Reports whether the active reservation is a paid locker rental.
 */
export function isLockerRentalReservation(locker) {
  const reservation = getLockerCurrentReservation(locker) || {};
  const reservationType = String(
    reservation.reservation_type || locker?.reservation_type || reservation.type || "",
  ).toLowerCase();
  const invoiceId = reservation.invoice_id ?? locker?.invoice_id;
  const price = getLockerRentalPrice(locker);

  return (
    reservationType === "rental" ||
    String(locker?.status || "").toLowerCase() === "rented" ||
    (invoiceId !== null && invoiceId !== undefined) ||
    (price !== null && price > 0)
  );
}

/**
 * Locker list records do not expose the reservation end date that determines
 * whether the backend requires a reason. Collect it for every active release
 * so an early release can never be submitted without one.
 */
export function doesLockerReleaseRequireReason(locker) {
  return Boolean(locker);
}

/**
 * Reads the paid rental amount from the supported locker response shapes.
 */
export function getLockerRentalPrice(locker) {
  const reservation = getLockerCurrentReservation(locker) || {};
  const rawPrice =
    reservation.price ??
    reservation.rental_price ??
    reservation.amount ??
    reservation.paid_amount ??
    locker?.rental_price ??
    locker?.price;
  const price = Number(rawPrice);

  return rawPrice !== "" && rawPrice !== null && rawPrice !== undefined && Number.isFinite(price)
    ? price
    : null;
}

/**
 * Preserves request-only reservation fields that are missing from some real
 * reservation responses (notably reservation_type and holder_type).
 */
export function createLockerReservationSnapshot(response, values = {}) {
  const reservation = getLockerRecord(response) || {};
  const holderType = values.holder_type || reservation.holder_type || "";
  const holderId =
    values.holder_id ??
    reservation.holder_id ??
    reservation.member_id ??
    reservation.staff_id ??
    reservation.coach_id ??
    null;

  return {
    ...reservation,
    reservation_type: values.reservation_type || reservation.reservation_type || "",
    holder_type: holderType,
    holder_id: holderId,
    price: values.price ?? reservation.price ?? null,
    start_date: values.start_date || reservation.start_date || "",
    end_date: values.end_date || reservation.end_date || "",
  };
}

/**
 * Builds the DELETE payload used when releasing an active reservation.
 */
export function createLockerReleasePayload(locker, values = {}) {
  if (!locker) return undefined;

  const isRefund = Boolean(values.is_refund);
  const payload = {
    reason: String(values.reason || "").trim(),
    is_refund: isRefund,
  };

  if (isRefund && values.refund_amount !== "" && values.refund_amount != null) {
    payload.refund_amount = Number(values.refund_amount);
  }

  return payload;
}

function getLockerHolder(locker) {
  const reservation = getLockerCurrentReservation(locker) || {};
  const relatedHolder = reservation.holder || locker?.holder || {};
  const rawType = String(
    locker?.holder_type || reservation.holder_type || relatedHolder.holder_type || "",
  ).toLowerCase();
  const typeFromValue = ["member", "coach", "staff", "guest"].find((type) =>
    rawType.includes(type),
  );
  const typeFromStatus = String(locker?.status || "").replace(/^with_/, "");
  const typeFromRelation = ["member", "coach", "staff"].find(
    (type) =>
      locker?.[type] ||
      reservation?.[type] ||
      relatedHolder?.[type] ||
      locker?.[`${type}_id`] != null ||
      reservation?.[`${type}_id`] != null ||
      relatedHolder?.[`${type}_id`] != null,
  );
  const type =
    typeFromValue ||
    (["member", "coach", "staff"].includes(typeFromStatus) ? typeFromStatus : "") ||
    typeFromRelation ||
    "";
  const typedRelation =
    locker?.[type] || reservation?.[type] || relatedHolder?.[type] || relatedHolder;
  const id =
    locker?.holder_id ??
    reservation?.holder_id ??
    relatedHolder?.holder_id ??
    locker?.[`${type}_id`] ??
    reservation?.[`${type}_id`] ??
    relatedHolder?.[`${type}_id`] ??
    typedRelation?.id ??
    null;
  const name =
    locker?.holder_name ||
    reservation?.holder_name ||
    getLockerPersonName(typedRelation) ||
    getLockerPersonName(relatedHolder);

  return { id, name, type };
}

/**
 * Reports whether a locker currently belongs to an active holder.
 */
export function isLockerOccupied(locker) {
  const holder = getLockerHolder(locker);
  const status = String(locker?.status || "").toLowerCase();
  return LOCKER_OCCUPIED_STATUSES.includes(status) || holder.id != null;
}

/**
 * Collapses backend reservation details into the four mutually-exclusive states
 * used by the locker page.
 */
export function getLockerPageState(locker) {
  const status = String(locker?.status || "").toLowerCase();
  const holder = getLockerHolder(locker);

  if (["maintenance", "disabled", "unavailable"].includes(status)) return "maintenance";
  if (holder.type === "member" || status === "with_member") return "with_member";
  if (
    holder.type === "coach" ||
    holder.type === "staff" ||
    ["with_coach", "with_staff", "with_staff_or_coach"].includes(status)
  ) {
    return "with_staff_or_coach";
  }
  if (status === "available" && holder.id == null) return "available";

  // Older responses used a generic occupied/assigned status for member lockers.
  if (isLockerOccupied(locker)) return "with_member";

  // Unknown inactive states must remain visible in the operational bucket.
  return "maintenance";
}

/**
 * Checks if a locker matches a specific status filter.
 */
export function matchesLockerStatus(locker, status) {
  if (!status || status === "all") return true;
  if (status === "occupied") return isLockerOccupied(locker);
  if (["available", "with_member", "with_staff_or_coach", "maintenance"].includes(status)) {
    return getLockerPageState(locker) === status;
  }
  if (status === "unavailable") {
    return getLockerPageState(locker) === "maintenance";
  }

  const reservation = getLockerCurrentReservation(locker) || {};
  const holder = getLockerHolder(locker);

  if (status === "assigned_free" || status === "free") {
    return (
      reservation.reservation_type === "assign" ||
      locker.reservation_type === "assign" ||
      reservation.type === "assign" ||
      (isLockerOccupied(locker) &&
        reservation.reservation_type !== "rental" &&
        !locker.rental_price)
    );
  }

  if (status === "rented") {
    return (
      reservation.reservation_type === "rental" ||
      locker.reservation_type === "rental" ||
      reservation.type === "rental" ||
      locker.status === "rented"
    );
  }

  if (status === "with_coach") {
    return (
      holder.type === "coach" ||
      locker.status === "with_coach" ||
      reservation.holder_type === "coach" ||
      Boolean(locker.coach || locker.coach_id)
    );
  }

  if (status === "with_staff") {
    return (
      holder.type === "staff" ||
      locker.status === "with_staff" ||
      reservation.holder_type === "staff" ||
      Boolean(locker.staff || locker.staff_id)
    );
  }

  return locker.status === status;
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
    const matchesStatus = matchesLockerStatus(locker, status);

    return matchesSearch && matchesBranch && matchesStatus;
  });
}

/**
 * Creates the exact query params supported by the locker list endpoint.
 */
export function createLockerQueryParams(branchFilter, statusFilter) {
  return {
    branch_id: branchFilter !== "all" ? String(branchFilter) : undefined,
    // Page states are derived from holder details, so filtering must happen only
    // after the complete branch collection has been received.
    status: undefined,
  };
}

/**
 * Builds the four page counters from the same classifier used by the filters.
 */
export function getLockerPageSummary(lockers) {
  const summary = {
    available_lockers_count: 0,
    unavailable_lockers_count: 0,
    assigned_to_member_count: 0,
    assigned_to_staff_or_coach_count: 0,
  };

  lockers.forEach((locker) => {
    const state = getLockerPageState(locker);

    if (state === "available") summary.available_lockers_count += 1;
    if (state === "maintenance") summary.unavailable_lockers_count += 1;
    if (state === "with_member") summary.assigned_to_member_count += 1;
    if (state === "with_staff_or_coach") summary.assigned_to_staff_or_coach_count += 1;
  });

  return summary;
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
  const holder = getLockerHolder(locker);
  if (holder.name) return holder.name;

  if (holder.type === "member" && holder.id) {
    return (
      memberOptions.find((member) => member.value === String(holder.id))?.label ||
      `لاعب #${holder.id}`
    );
  }

  if (holder.type === "staff" && holder.id) {
    return (
      staffOptions.find((staff) => staff.value === String(holder.id))?.label || `موظف #${holder.id}`
    );
  }

  if (holder.type === "coach" && holder.id) {
    return (
      coachOptions.find((coach) => coach.value === String(holder.id))?.label || `كوتش #${holder.id}`
    );
  }

  if (holder.type === "guest" && holder.id) {
    return `زائر #${holder.id}`;
  }

  return holder.id ? `مستفيد #${holder.id}` : "";
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
    reason: "",
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
    reason: form.reason.trim(),
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
  };

  if (form.reservation_type === "rental" && form.price !== "") {
    payload.price = Number(form.price);
  }
  if (form.holder_type !== "coach") {
    if (form.start_date) payload.start_date = form.start_date;
    if (form.end_date) payload.end_date = form.end_date;
  }

  return payload;
}
