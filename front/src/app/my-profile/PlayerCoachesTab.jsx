"use client";

import { useState, useMemo } from "react";
import { useGetCoachesQuery } from "@/lib/api/coachesApi";
import { useGetActivityTypesQuery } from "@/lib/api/activitiesApi";
import { resolveStaffPhotoUrl } from "@/app/management/staff/staffUtils";

const DAYS = ["الكل", "الأحد", "الإثنين", "الثلاثاء", "الأربعاء", "الخميس", "الجمعة", "السبت"];

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

export default function PlayerCoachesTab({ branchId }) {
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

  const coaches = useMemo(() => {
    const list = coachesResponse?.data?.data || coachesResponse?.data || coachesResponse || [];
    const rawList = Array.isArray(list) ? list : [];
    if (!branchId) return rawList;
    return rawList.filter((coach) => {
      if (!coach.branches || coach.branches.length === 0) return true;
      return coach.branches.some(
        (b) => String(b.id || b.branch_id) === String(branchId),
      );
    });
  }, [coachesResponse, branchId]);

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
              const branchName = coach.branch?.name || coach.branches?.[0]?.name || "فرع النادي";

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
                      <p className="truncate text-xs text-app-muted-light mt-0.5">{branchName}</p>
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
