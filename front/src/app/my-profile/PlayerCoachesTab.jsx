"use client";

import { useState, useMemo } from "react";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetActivityTypesQuery } from "@/lib/api/activitiesApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { COACH_ACTIVITY_KINDS, getCoachActivityKind } from "@/app/management/coaches/coachFormUtils";
import { DAYS_OF_WEEK } from "@/app/management/coaches/coachConstants";
import { formatLocalizedName, getBranchesArray } from "@/lib/utils";

const DAYS = ["الكل", "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];

function normalizeArabicText(text) {
  if (!text || typeof text !== "string") return "";
  return text
    .trim()
    .replace(/[إأآا]/g, "ا")
    .replace(/ة/g, "ه")
    .replace(/ى/g, "ي")
    .toLowerCase();
}

function activityMatchesType(act, selectedType) {
  if (!act || !selectedType || selectedType === "الكل") return true;

  const cleanSelected = selectedType.trim();
  const normSelected = normalizeArabicText(cleanSelected);

  // 1. Direct type name matching
  const typeObj = act.activity_type || act.activityType || act.type || {};
  const typeName =
    typeof typeObj?.name === "object"
      ? typeObj?.name?.ar || typeObj?.name?.en || ""
      : typeObj?.name || (typeof typeObj === "string" ? typeObj : "");

  const normTypeName = normalizeArabicText(typeName);
  if (
    normTypeName &&
    (normTypeName === normSelected ||
      normTypeName.includes(normSelected) ||
      normSelected.includes(normTypeName))
  ) {
    return true;
  }

  // 2. Activity name matching
  const actName =
    typeof act.name === "object"
      ? act.name?.ar || act.name?.en || ""
      : act.name || "";

  const normActName = normalizeArabicText(actName);
  if (
    normActName &&
    (normActName === normSelected || normActName.includes(normSelected))
  ) {
    return true;
  }

  // 3. Normalized kind matching using getCoachActivityKind
  const kind = getCoachActivityKind(act);

  if (
    normSelected.includes("تدريب عام") ||
    normSelected.includes("اجهزه عام") ||
    normSelected.includes("عام")
  ) {
    if (kind === COACH_ACTIVITY_KINDS.GENERAL_TRAINING) return true;
    if (normActName.includes("عام")) return true;
  }

  if (
    normSelected.includes("تدريب خاص") ||
    normSelected.includes("اجهزه خاص") ||
    normSelected.includes("خاص")
  ) {
    if (kind === COACH_ACTIVITY_KINDS.PRIVATE_TRAINING) return true;
    if (Boolean(act.is_private_equipment) || Number(act.is_private_equipment) === 1) return true;
    if (normActName.includes("خاص")) return true;
  }

  if (
    normSelected.includes("حصه جماعيه") ||
    normSelected.includes("فعاليه") ||
    normSelected.includes("فعاليات") ||
    normSelected.includes("جماعي")
  ) {
    if (kind === COACH_ACTIVITY_KINDS.GROUP_CLASS) return true;
    if (
      normActName.includes("جماعي") ||
      normActName.includes("حصه") ||
      normActName.includes("فعاليه")
    ) {
      return true;
    }
  }

  return false;
}

function coachMatchesActivityType(coach, selectedType) {
  if (!selectedType || selectedType === "الكل") return true;

  const activities = Array.isArray(coach.activities)
    ? coach.activities
    : Array.isArray(coach.specialized_activities)
      ? coach.specialized_activities
      : Array.isArray(coach.details?.activities)
        ? coach.details.activities
        : [];

  if (activities.length > 0) {
    return activities.some((act) => activityMatchesType(act, selectedType));
  }

  const normSelected = normalizeArabicText(selectedType);
  const workTypes = Array.isArray(coach.work_types)
    ? coach.work_types
    : Array.isArray(coach.details?.work_types)
      ? coach.details.work_types
      : [];

  if (normSelected.includes("جماعي") || normSelected.includes("فعاليه")) {
    return workTypes.includes("activities");
  }

  if (coach.specialization && typeof coach.specialization === "string") {
    return normalizeArabicText(coach.specialization).includes(normSelected);
  }

  return false;
}

function coachMatchesDay(coach, selectedDay) {
  if (!selectedDay || selectedDay === "الكل") return true;

  const normSelected = normalizeArabicText(selectedDay);

  const daysList = coach.working_days || coach.days || coach.work_days || coach.available_days;
  if (Array.isArray(daysList) && daysList.length > 0) {
    return daysList.some((d) => {
      if (typeof d === "string") {
        return (
          normalizeArabicText(d).includes(normSelected) ||
          normSelected.includes(normalizeArabicText(d))
        );
      }
      if (typeof d === "number") {
        return normalizeArabicText(DAYS_OF_WEEK[d] || "") === normSelected;
      }
      if (typeof d === "object" && d) {
        const dName = d.name?.ar || d.name?.en || d.name || d.day || d.day_name;
        return (
          dName &&
          (normalizeArabicText(dName).includes(normSelected) ||
            normSelected.includes(normalizeArabicText(dName)))
        );
      }
      return false;
    });
  }

  const schedules = coach.schedules || coach.coach_schedules || coach.shifts;
  if (Array.isArray(schedules) && schedules.length > 0) {
    const hasDayProperty = schedules.some(
      (s) => s && (s.day || s.day_of_week !== undefined || s.day_name || s.days)
    );
    if (hasDayProperty) {
      return schedules.some((s) => {
        if (!s) return false;
        if (s.day && typeof s.day === "string") {
          return (
            normalizeArabicText(s.day).includes(normSelected) ||
            normSelected.includes(normalizeArabicText(s.day))
          );
        }
        if (s.day_name && typeof s.day_name === "string") {
          return (
            normalizeArabicText(s.day_name).includes(normSelected) ||
            normSelected.includes(normalizeArabicText(s.day_name))
          );
        }
        if (s.day_of_week !== undefined) {
          return normalizeArabicText(DAYS_OF_WEEK[s.day_of_week] || "") === normSelected;
        }
        if (Array.isArray(s.days)) {
          return s.days.some(
            (d) =>
              normalizeArabicText(String(d)).includes(normSelected) ||
              normalizeArabicText(DAYS_OF_WEEK[d] || "") === normSelected
          );
        }
        return false;
      });
    }
  }

  return true;
}

function getCoachPhotoSrc(photoRaw) {
  if (!photoRaw || typeof photoRaw !== "string") return "";
  const trimmed = photoRaw.trim();
  if (!trimmed) return "";
  if (trimmed.startsWith("data:") || trimmed.startsWith("blob:")) return trimmed;
  if (trimmed.startsWith("http://") || trimmed.startsWith("https://")) return trimmed;

  const clean = trimmed.replace(/^\/+/, "");
  return `https://technogym.iss-group.me/${clean}`;
}

function CoachAvatar({ name, photoRaw }) {
  const [hasError, setHasError] = useState(false);
  const photoUrl = useMemo(() => getCoachPhotoSrc(photoRaw), [photoRaw]);
  const initialLetter = name?.trim()?.charAt(0) || "م";

  if (!photoUrl || hasError) {
    return (
      <div className="grid size-full place-items-center bg-gradient-to-br from-app-card-hover to-app-card-soft text-xl font-bold text-app-yellow border border-app-line/40">
        {initialLetter}
      </div>
    );
  }

  return (
    <img
      src={photoUrl}
      alt={name}
      className="size-full object-cover"
      onError={() => setHasError(true)}
      loading="lazy"
    />
  );
}

function resolveCoachBranchName(coach, branches = [], fallbackBranchId = null) {
  if (!coach) return "";

  // 1. Direct coach.branch object or string
  if (coach.branch) {
    if (typeof coach.branch === "string" && coach.branch.trim() !== "فرع النادي") {
      return coach.branch.trim();
    }
    if (typeof coach.branch === "object") {
      const directName = formatLocalizedName(coach.branch.name || coach.branch.branch_name);
      if (directName && directName !== "-" && directName !== "فرع النادي") {
        return directName;
      }
      const bId = coach.branch.id || coach.branch.branch_id;
      if (bId) {
        const match = branches.find((b) => String(b.id) === String(bId));
        if (match) return formatLocalizedName(match.name);
      }
    }
  }

  // 2. coach.branches array
  if (Array.isArray(coach.branches) && coach.branches.length > 0) {
    const names = coach.branches
      .map((b) => {
        if (!b) return "";
        if (typeof b === "string" && b.trim() !== "فرع النادي") return b.trim();
        if (typeof b === "number") {
          const match = branches.find((candidate) => String(candidate.id) === String(b));
          return match ? formatLocalizedName(match.name) : "";
        }
        if (typeof b === "object") {
          const bName = formatLocalizedName(b.name || b.branch_name || b.branch?.name);
          if (bName && bName !== "-" && bName !== "فرع النادي") return bName;
          const bId = b.id || b.branch_id || b.branch?.id;
          if (bId) {
            const match = branches.find((candidate) => String(candidate.id) === String(bId));
            if (match) return formatLocalizedName(match.name);
          }
        }
        return "";
      })
      .filter((n) => Boolean(n) && n !== "-");

    if (names.length > 0) {
      return names.join("، ");
    }
  }

  // 3. coach.branch_ids or coach.branch_id
  const branchIds = Array.isArray(coach.branch_ids)
    ? coach.branch_ids
    : Array.isArray(coach.details?.branch_ids)
      ? coach.details.branch_ids
      : coach.branch_id
        ? [coach.branch_id]
        : coach.details?.branch_id
          ? [coach.details.branch_id]
          : [];

  if (branchIds.length > 0) {
    const names = branchIds
      .map((id) => {
        const match = branches.find((candidate) => String(candidate.id) === String(id));
        return match ? formatLocalizedName(match.name) : "";
      })
      .filter((n) => Boolean(n) && n !== "-");

    if (names.length > 0) {
      return names.join("، ");
    }
  }

  // 4. Fallback branchId from props or current player's branch
  if (fallbackBranchId) {
    const match = branches.find((candidate) => String(candidate.id) === String(fallbackBranchId));
    if (match) return formatLocalizedName(match.name);
  }

  // 5. If only 1 branch in system
  if (branches.length === 1 && branches[0]?.name) {
    return formatLocalizedName(branches[0].name);
  }

  return "";
}

export default function PlayerCoachesTab({ branchId, branches: propBranches = [] }) {
  const [selectedDay, setSelectedDay] = useState("الكل");
  const [selectedActivityType, setSelectedActivityType] = useState("الكل");

  const queryParams = useMemo(() => {
    const params = { per_page: "all" };
    if (branchId) params.branch_id = branchId;
    if (selectedDay !== "الكل") params.day = selectedDay;
    if (selectedActivityType !== "الكل") params.activity_type = selectedActivityType;
    return params;
  }, [branchId, selectedDay, selectedActivityType]);

  const { data: coachesResponse, isLoading, isFetching } = useGetCoachesQuery(queryParams);
  const { data: activityTypesResponse } = useGetActivityTypesQuery({ per_page: "all" });
  const { data: branchesResponse } = useGetBranchesQuery({ per_page: "all" });

  const branches = useMemo(() => {
    const list = getBranchesArray(branchesResponse);
    if (list.length > 0) return list;
    return Array.isArray(propBranches) ? propBranches : [];
  }, [branchesResponse, propBranches]);

  const coaches = useMemo(() => {
    const list = coachesResponse?.data?.data || coachesResponse?.data || coachesResponse || [];
    const rawList = Array.isArray(list) ? list : [];
    let filtered = rawList;

    // Filter by branch
    if (branchId) {
      filtered = filtered.filter((coach) => {
        if (!coach.branches || coach.branches.length === 0) return true;
        return coach.branches.some(
          (b) => String(b.id || b.branch_id) === String(branchId),
        );
      });
    }

    // Filter by day (client-side fallback)
    if (selectedDay !== "الكل") {
      filtered = filtered.filter((coach) => coachMatchesDay(coach, selectedDay));
    }

    // Filter by activity type (client-side fallback)
    if (selectedActivityType !== "الكل") {
      filtered = filtered.filter((coach) => coachMatchesActivityType(coach, selectedActivityType));
    }

    return filtered;
  }, [coachesResponse, branchId, selectedDay, selectedActivityType]);

  // Stable activity types: constant across all days
  const activityTypes = useMemo(() => {
    const list = activityTypesResponse?.data || activityTypesResponse || [];
    const rawTypes = Array.isArray(list) ? list : [];

    const seenNames = new Set();
    const uniqueTypes = [];

    rawTypes.forEach((type) => {
      const rawName =
        typeof type.name === "object" ? type.name.ar || type.name.en : type.name;
      const cleanName = (rawName || "").trim();
      if (!cleanName || seenNames.has(cleanName)) return;
      seenNames.add(cleanName);
      uniqueTypes.push({
        id: type.id || cleanName,
        name: cleanName,
      });
    });

    return uniqueTypes;
  }, [activityTypesResponse]);

  const isDataLoading = isLoading || isFetching;

  return (
    <div className="space-y-6">
      {/* Filters Section */}
      <section className="rounded-2xl border border-app-line bg-app-card-soft/45 p-4 sm:p-5 space-y-4">
        <div>
          <h3 className="text-sm font-semibold text-white mb-2">الفلترة حسب اليوم:</h3>
          <div className="flex flex-wrap gap-1.5 sm:gap-2">
            {DAYS.map((day) => {
              const isSelected = selectedDay === day;
              return (
                <button
                  key={day}
                  type="button"
                  onClick={() => setSelectedDay(day)}
                  className={`rounded-xl px-3 py-1.5 text-xs font-medium transition-all ${
                    isSelected
                      ? "bg-app-yellow text-black font-semibold shadow-sm"
                      : "border border-app-line bg-app-card-soft text-app-muted-light hover:text-white hover:border-app-yellow/50"
                  }`}
                >
                  {day}
                </button>
              );
            })}
          </div>
        </div>

        <div>
          <h3 className="text-sm font-semibold text-white mb-2">الفلترة حسب نوع النشاط:</h3>
          <div className="flex flex-wrap gap-1.5 sm:gap-2">
            <button
              type="button"
              onClick={() => setSelectedActivityType("الكل")}
              className={`rounded-xl px-3 py-1.5 text-xs font-medium transition-all ${
                selectedActivityType === "الكل"
                  ? "bg-app-yellow text-black font-semibold shadow-sm"
                  : "border border-app-line bg-app-card-soft text-app-muted-light hover:text-white hover:border-app-yellow/50"
              }`}
            >
              الكل
            </button>
            {activityTypes.map((type) => {
              const isSelected = selectedActivityType === type.name;
              return (
                <button
                  key={type.id || type.name}
                  type="button"
                  onClick={() => setSelectedActivityType(type.name)}
                  className={`rounded-xl px-3 py-1.5 text-xs font-medium transition-all ${
                    isSelected
                      ? "bg-app-yellow text-black font-semibold shadow-sm"
                      : "border border-app-line bg-app-card-soft text-app-muted-light hover:text-white hover:border-app-yellow/50"
                  }`}
                >
                  {type.name}
                </button>
              );
            })}
          </div>
        </div>
      </section>

      {/* Coaches Grid */}
      <section>
        <div className="mb-4 flex items-center justify-between">
          <h2 className="text-base font-semibold text-white">قائمة المدربين ({coaches.length})</h2>
          {(selectedDay !== "الكل" || selectedActivityType !== "الكل") && (
            <span className="text-xs text-app-yellow">
              مفلترة: {selectedDay !== "الكل" ? `يوم ${selectedDay}` : ""}{" "}
              {selectedActivityType !== "الكل" ? `• ${selectedActivityType}` : ""}
            </span>
          )}
        </div>

        {isDataLoading ? (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {[1, 2, 3, 4, 5, 6].map((item) => (
              <div
                key={item}
                className="h-44 animate-pulse rounded-2xl border border-app-line bg-app-card-soft/50 p-4"
              />
            ))}
          </div>
        ) : coaches.length === 0 ? (
          <div className="rounded-2xl border border-dashed border-app-line p-8 text-center text-sm text-app-muted-light">
            لا يوجد مدربون متاحون وفق الفلاتر المحددة حالياً.
          </div>
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {coaches.map((coach) => {
              const name = coach.full_name || coach.person?.full_name || `مدرب #${coach.id}`;
              const photoRaw = coach.photo_url || coach.person?.photo_url || coach.person?.photo || coach.photo || null;
              const activities = Array.isArray(coach.activities)
                ? coach.activities
                : Array.isArray(coach.specialized_activities)
                  ? coach.specialized_activities
                  : [];
              const branchName =
                resolveCoachBranchName(coach, branches, branchId) ||
                (branches[0] ? formatLocalizedName(branches[0].name) : "");

              return (
                <div
                  key={coach.id}
                  className="rounded-2xl border border-app-line bg-app-card-soft/60 p-4 sm:p-5 transition hover:border-app-yellow/40 hover:bg-app-card-soft"
                >
                  <div className="flex items-center gap-3">
                    <div className="relative size-14 shrink-0 overflow-hidden rounded-xl border border-app-line bg-black/30">
                      <CoachAvatar name={name} photoRaw={photoRaw} />
                    </div>

                    <div className="min-w-0 flex-1">
                      <h4 className="truncate text-base font-semibold text-white">{name}</h4>
                      {branchName && (
                        <p className="truncate text-xs text-app-muted-light mt-0.5">{branchName}</p>
                      )}
                    </div>
                  </div>

                  {activities.length > 0 && (
                    <div className="mt-4 pt-3 border-t border-app-line/50">
                      <p className="text-[11px] text-app-muted-light mb-1.5">الأنشطة والحصص:</p>
                      <div className="flex flex-wrap gap-1.5">
                        {activities.map((act, index) => {
                          const actName =
                            typeof act.name === "object"
                              ? act.name.ar || act.name.en
                              : act.name || "نشاط";
                          const typeName =
                            typeof act.activity_type?.name === "object"
                              ? act.activity_type?.name?.ar || act.activity_type?.name?.en
                              : act.activity_type?.name;

                          return (
                            <span
                              key={act.id || index}
                              className="rounded-md bg-white/5 border border-app-line/40 px-2 py-0.5 text-[11px] text-white/90"
                            >
                              {actName}
                              {typeName ? ` (${typeName})` : ""}
                            </span>
                          );
                        })}
                      </div>
                    </div>
                  )}

                  {coach.work_status && (
                    <div className="mt-3 flex items-center justify-between text-[11px] text-app-muted-light pt-2">
                      <span>حالة العمل:</span>
                      <span className="text-emerald-400 font-medium">نشط في النادي ✓</span>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        )}
      </section>
    </div>
  );
}
