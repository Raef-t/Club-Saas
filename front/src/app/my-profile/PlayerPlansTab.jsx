"use client";

import { useState, useMemo } from "react";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useGetActivityTypesQuery } from "@/lib/api/activitiesApi";

export default function PlayerPlansTab({ branchId }) {
  const [selectedActivityType, setSelectedActivityType] = useState("الكل");

  const queryParams = useMemo(() => {
    const params = { per_page: "all" };
    if (branchId) params.branch_id = branchId;
    if (selectedActivityType !== "الكل") params.activity_type = selectedActivityType;
    return params;
  }, [branchId, selectedActivityType]);

  const { data: plansResponse, isLoading, isFetching } = useGetSubscriptionPlansQuery(queryParams);
  const { data: activityTypesResponse } = useGetActivityTypesQuery({ per_page: "all" });

  // Stable unique activity types
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

  const plans = useMemo(() => {
    const list = plansResponse?.data?.data || plansResponse?.data || plansResponse || [];
    let activePlans = Array.isArray(list) ? list.filter((p) => p.is_active !== false && p.status !== "inactive") : [];

    // Filter by branch
    if (branchId) {
      activePlans = activePlans.filter((p) => !p.branch_id || String(p.branch_id) === String(branchId));
    }

    // Client-side filter fallback for activity type
    if (selectedActivityType !== "الكل") {
      activePlans = activePlans.filter((p) => {
        const planName = (typeof p.name === "object" ? p.name.ar || p.name.en : p.name) || "";
        const activities = Array.isArray(p.activities)
          ? p.activities
          : Array.isArray(p.plan_activities)
            ? p.plan_activities
            : [];

        const hasMatchingActivity = activities.some((act) => {
          const type = act.activity_type || act.activityType || act.activity?.activity_type;
          const typeName = typeof type?.name === "object" ? type?.name?.ar || type?.name?.en : type?.name;
          const actName = typeof act.name === "object" ? act.name?.ar || act.name?.en : act.name || act.activity?.name;

          if (typeName && typeName.trim() === selectedActivityType) return true;
          if (actName && actName.trim() === selectedActivityType) return true;
          if (selectedActivityType === "تدريب خاص" && (act.is_private_equipment || act.activity?.is_private_equipment)) return true;
          return false;
        });

        return hasMatchingActivity || planName.includes(selectedActivityType);
      });
    }

    return activePlans;
  }, [plansResponse, branchId, selectedActivityType]);

  const isDataLoading = isLoading || isFetching;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
          <h2 className="text-base font-semibold text-white">الفعاليات وباقات الاشتراك</h2>
          <p className="text-xs text-app-muted-light mt-0.5">
            استعرض الباقات والفعاليات المتاحة في النادي مع إمكانية التصفية حسب نوع النشاط.
          </p>
        </div>
      </div>

      {/* Filter by Activity Type */}
      <section className="rounded-2xl border border-app-line bg-app-card-soft/45 p-4 sm:p-5">
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
      </section>

      {/* Plans Grid */}
      {isDataLoading ? (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {[1, 2, 3].map((item) => (
            <div
              key={item}
              className="h-56 animate-pulse rounded-2xl border border-app-line bg-app-card-soft/50 p-5"
            />
          ))}
        </div>
      ) : plans.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-app-line p-8 text-center text-sm text-app-muted-light">
          لا توجد فعاليات أو باقات معروضة حالياً {selectedActivityType !== "الكل" ? `لنوع النشاط (${selectedActivityType})` : ""}.
        </div>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {plans.map((plan) => {
            const planName =
              typeof plan.name === "object"
                ? plan.name.ar || plan.name.en
                : plan.name || "باقة رياضية";
            const duration = plan.duration_days
              ? `${plan.duration_days} يوم`
              : plan.duration_months
                ? `${plan.duration_months} شهر`
                : "اشتراك دوري";
            const sessionsCount = plan.session_count || plan.sessions_count
              ? `${plan.session_count || plan.sessions_count} حصة`
              : "حصص غير محدودة";
            const activities = Array.isArray(plan.activities)
              ? plan.activities
              : Array.isArray(plan.plan_activities)
                ? plan.plan_activities
                : [];

            return (
              <div
                key={plan.id}
                className="flex flex-col justify-between rounded-2xl border border-app-line bg-app-card-soft/60 p-5 transition hover:border-app-yellow/40 hover:bg-app-card-soft"
              >
                <div>
                  <div className="flex items-start justify-between gap-3">
                    <h3 className="text-lg font-bold text-white">{planName}</h3>
                    <span className="shrink-0 rounded-full bg-app-yellow/15 px-2.5 py-0.5 text-xs font-semibold text-app-yellow">
                      {duration}
                    </span>
                  </div>

                  <div className="mt-4 space-y-2 border-t border-app-line/50 pt-3 text-xs text-app-muted-light">
                    <div className="flex items-center gap-2">
                      <span className="text-app-yellow">✓</span>
                      <span>عدد الحصص: {sessionsCount}</span>
                    </div>

                    {activities.length > 0 && (
                      <div className="pt-2">
                        <p className="mb-1.5 text-[11px] text-white/80">الأنشطة المشمولة:</p>
                        <div className="flex flex-wrap gap-1">
                          {activities.map((act, i) => {
                            const actName =
                              typeof act.name === "object"
                                ? act.name.ar || act.name.en
                                : act.name || act.activity?.name || "نشاط";
                            return (
                              <span
                                key={i}
                                className="rounded bg-black/30 px-2 py-0.5 text-[10px] text-app-muted-light"
                              >
                                {actName}
                              </span>
                            );
                          })}
                        </div>
                      </div>
                    )}
                  </div>
                </div>

                {plan.description && (
                  <p className="mt-4 text-xs text-app-muted line-clamp-2">{plan.description}</p>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
