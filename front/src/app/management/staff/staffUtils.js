import { formatLocalizedName } from "@/lib/utils";
import {
  resolveWorkStatus,
  WORK_STATUSES,
  WORK_STATUS_CLASSES,
  WORK_STATUS_LABELS,
} from "@/lib/workStatus";
import { STAFF_WORK_STATUS_LABELS } from "./staffConstants";

const MANAGER_STAFF_ROLES = new Set(["admin", "management_admin", "manager"]);

export function isManagerStaffRole(role) {
  return MANAGER_STAFF_ROLES.has(String(role || ""));
}

/**
 * Identifies coach records returned by either the staff role or a coach relation.
 */
export function isCoachStaff(staff) {
  const role = staff?.role?.slug || staff?.role?.name || staff?.role;
  return (
    String(role || "").toLowerCase() === "coach" ||
    (staff?.coach_id !== null && staff?.coach_id !== undefined) ||
    (staff?.coach?.id !== null && staff?.coach?.id !== undefined)
  );
}

/**
 * Routes coaches through the coach form while keeping every other role in the staff form.
 */
export function getStaffEditHref(staff) {
  const isCoach = isCoachStaff(staff);
  const recordId = isCoach ? (staff?.coach_id ?? staff?.coach?.id ?? staff?.id) : staff?.id;
  const pathname = isCoach ? "/management/coaches/create" : "/management/staff/create";

  return `${pathname}?mode=edit&id=${encodeURIComponent(recordId ?? "")}`;
}

export function getStaffCollection(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

export function getStaffRecord(response) {
  const candidate = response?.data?.staff || response?.data?.data || response?.data || response;
  return candidate && typeof candidate === "object" && !Array.isArray(candidate) ? candidate : null;
}

export function buildStaffQueryParams({ branchId, role, gender, workStatus }) {
  const params = {};

  if (branchId && branchId !== "all") params.branch_id = Number(branchId);
  if (role && role !== "all") params.role = role;
  if (gender && gender !== "all") params.gender = gender;
  if (WORK_STATUSES.includes(workStatus)) params.work_status = workStatus;

  return params;
}

export function getStaffWorkStatusMeta(recordOrStatus) {
  const value = resolveWorkStatus(recordOrStatus);
  const isCoach = typeof recordOrStatus === "object" && isCoachStaff(recordOrStatus);
  return {
    value,
    label: isCoach ? WORK_STATUS_LABELS[value] : STAFF_WORK_STATUS_LABELS[value],
    className: WORK_STATUS_CLASSES[value],
  };
}

export function splitStaffName(person = {}) {
  const fullName = String(person.full_name || "").trim();
  const [firstPart = "", ...remainingParts] = fullName.split(/\s+/).filter(Boolean);

  return {
    firstName: person.first_name || firstPart,
    lastName: person.last_name || remainingParts.join(" "),
  };
}

export function getStaffBranchNames(staff, branches = []) {
  if (Array.isArray(staff?.branch_name) && staff.branch_name.length) {
    return staff.branch_name.map(formatLocalizedName).filter(Boolean);
  }

  if (typeof staff?.branch_name === "string" && staff.branch_name.trim()) {
    return [staff.branch_name.trim()];
  }

  if (Array.isArray(staff?.branches) && staff.branches.length) {
    return staff.branches
      .map((branch) => formatLocalizedName(branch?.name || branch))
      .filter(Boolean);
  }

  const branchIds = Array.isArray(staff?.branch_ids) ? staff.branch_ids.map(String) : [];
  return branchIds.map((id) => {
    const branch = branches.find((item) => String(item.id) === id);
    return branch ? formatLocalizedName(branch.name) : `فرع #${id}`;
  });
}

export function getStaffBranchIds(staff, branches = []) {
  const explicitIds = Array.isArray(staff?.branch_ids) ? staff.branch_ids : [];
  const relatedIds = Array.isArray(staff?.branches)
    ? staff.branches.map((branch) => (typeof branch === "object" ? branch.id : branch))
    : [];
  const shiftIds = Array.isArray(staff?.shifts)
    ? staff.shifts.map((shift) => shift?.branch_shift?.branch_id)
    : [];
  const namedIds = getStaffBranchNames(staff, branches).map((name) => {
    const branch = branches.find((item) => formatLocalizedName(item.name) === name);
    return branch?.id;
  });

  return [...new Set([...explicitIds, ...relatedIds, ...shiftIds, ...namedIds])]
    .map(Number)
    .filter((id) => Number.isFinite(id) && id > 0);
}

export function createStaffInitialValues({ staff, branches = [], selectedBranchId = "all" } = {}) {
  const defaultBranchId =
    selectedBranchId !== "all" &&
    branches.some((branch) => String(branch.id) === String(selectedBranchId))
      ? Number(selectedBranchId)
      : branches[0]?.id
        ? Number(branches[0].id)
        : null;

  if (!staff) {
    return {
      first_name: "",
      last_name: "",
      country_code: "+963",
      phone_number: "",
      role: "receptionist",
      employment_type: "fixed_salary",
      base_salary: "0",
      work_status: "active",
      is_active: true,
      start_date: "",
      start_time: "",
      end_time: "",
      address: "",
      branch_ids: defaultBranchId ? [defaultBranchId] : [],
      reason: "",
    };
  }

  const { firstName, lastName } = splitStaffName(staff.person);

  return {
    first_name: firstName,
    last_name: lastName,
    country_code: staff.person?.country_code || "+963",
    phone_number: staff.person?.phone_number || "",
    role: staff.role || "staff",
    employment_type: staff.employment_type || "fixed_salary",
    base_salary: String(Number(staff.base_salary) || 0),
    work_status: resolveWorkStatus(staff),
    is_active: resolveWorkStatus(staff) === "active",
    start_date: String(staff.start_date || "").split("T")[0],
    start_time: String(staff.start_time || "").slice(0, 5),
    end_time: String(staff.end_time || "").slice(0, 5),
    address: staff.person?.address || "",
    branch_ids: getStaffBranchIds(staff, branches),
    reason: "",
  };
}

/**
 * Keeps the required employment fields when the signed-in staff member edits
 * only the personal fields exposed by the profile drawer.
 */
export function createStaffProfileUpdateBody(staff, values) {
  const person = staff?.person || {};
  const body = {
    first_name: values.first_name.trim(),
    last_name: values.last_name.trim(),
    phone_number: values.phone_number.trim(),
    role: staff?.role,
    employment_type: staff?.employment_type,
    base_salary: String(Number(staff?.base_salary) || 0),
    work_status: resolveWorkStatus(staff),
    reason: values.reason.trim(),
  };

  if (values.country_code) body.country_code = values.country_code.trim();
  if (values.gender) body.gender = values.gender;
  if (staff?.start_date) body.start_date = String(staff.start_date).split("T")[0];
  if (staff?.start_time) body.start_time = staff.start_time;
  if (staff?.end_time) body.end_time = staff.end_time;
  if (person.address) body.address = person.address;

  const branchIds = getStaffBranchIds(staff);
  if (branchIds.length) body.branch_ids = branchIds;

  return body;
}

export function resolveStaffPhotoUrl(value) {
  if (!value || typeof value !== "string") return "";
  if (value.startsWith("http") || value.startsWith("blob:")) return value;
  return `http://31.70.108.63/${value.replace(/^\//, "")}`;
}
