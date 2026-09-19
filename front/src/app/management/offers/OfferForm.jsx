"use client";

import { useEffect, useMemo, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Dropdown from "@/components/ui/Dropdown";
import SearchInput from "@/components/ui/SearchInput";
import { Field, CheckboxField, TextAreaField } from "@/components/forms/FormControls";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useGetActivityTypesQuery } from "@/lib/api/activitiesApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { formatMoney, formatLocalizedName } from "@/lib/utils";
import { offerSchema } from "@/lib/validations/offersSchema";
import { withAllItems } from "@/lib/pagination";
import { getEntityBranchIds, getPreferredBranchId } from "@/lib/managementBranchUtils";
import OfferFormSection from "./_components/OfferFormSection";

const OFFER_TYPE_OPTIONS = [
  { value: "bundle", label: "باقة" },
  { value: "single_choice", label: "يختار المشترك فعالية واحدة" },
];

function normalizeArabicText(str) {
  return (str || "")
    .trim()
    .replace(/[أإآ]/g, "ا")
    .replace(/ة/g, "ه")
    .replace(/ى/g, "ي")
    .toLowerCase();
}

export default function OfferForm({
  mode = "create",
  initialValues = null,
  onSubmit,
  onCancel,
  isLoading = false,
  errorMessage = "",
  formId = "offer-form",
  showFooterActions = true,
  branches: initialBranches = [],
}) {
  const { selectedBranchId } = useManagementBranch();

  const { data: branchesData } = useGetBranchesQuery(withAllItems());
  const branches = useMemo(() => {
    if (initialBranches && initialBranches.length > 0) return initialBranches;
    if (Array.isArray(branchesData?.data?.data)) return branchesData.data.data;
    if (Array.isArray(branchesData?.data)) return branchesData.data;
    if (Array.isArray(branchesData)) return branchesData;
    return [];
  }, [initialBranches, branchesData]);

  const [form, setForm] = useState(() => {
    const defaultBranch = getPreferredBranchId({
      currentBranchId: initialValues?.branch_id,
      selectedBranchId,
      branches,
    });

    return {
      branch_id: defaultBranch ? String(defaultBranch) : String(selectedBranchId || ""),
      offer_type: initialValues?.offer_type || "bundle",
      name: initialValues?.name || "",
      description: initialValues?.description || "",
      price:
        initialValues?.price !== undefined && initialValues?.price !== null
          ? String(initialValues.price)
          : "",
      start_date: initialValues?.start_date || "",
      end_date: initialValues?.end_date || "",
      is_active: initialValues?.is_active !== undefined ? Boolean(initialValues.is_active) : true,
      plans: Array.isArray(initialValues?.plans)
        ? initialValues.plans.map((p) => Number(p.id ?? p))
        : [],
    };
  });

  const [errors, setErrors] = useState({});
  const [selectedActivityTypeId, setSelectedActivityTypeId] = useState("all");
  const [planSearchTerm, setPlanSearchTerm] = useState("");
  const [isNameManuallyEdited, setIsNameManuallyEdited] = useState(
    () => mode === "edit" || Boolean(initialValues?.name?.trim()),
  );
  const [isUnlimitedDuration, setIsUnlimitedDuration] = useState(
    () => !initialValues?.start_date && !initialValues?.end_date,
  );

  // Sync branch silently from context if needed
  useEffect(() => {
    const effectiveBranch = getPreferredBranchId({
      currentBranchId: form.branch_id,
      selectedBranchId,
      branches,
    });
    if (effectiveBranch && String(effectiveBranch) !== String(form.branch_id)) {
      setForm((prev) => ({ ...prev, branch_id: String(effectiveBranch) }));
    }
  }, [selectedBranchId, branches, form.branch_id]);

  // Load activity types and deduplicate them
  const { data: activityTypesData } = useGetActivityTypesQuery(withAllItems());
  const rawActivityTypes = useMemo(() => {
    if (Array.isArray(activityTypesData?.data?.data)) return activityTypesData.data.data;
    if (Array.isArray(activityTypesData?.data)) return activityTypesData.data;
    if (Array.isArray(activityTypesData)) return activityTypesData;
    return [];
  }, [activityTypesData]);

  const activityTypeOptions = useMemo(() => {
    const seen = new Set();
    const uniqueOptions = [];

    for (const type of rawActivityTypes) {
      if (!type) continue;
      const rawName = formatLocalizedName(type.name) || type.name || "";
      const trimmedName = rawName.trim();
      if (!trimmedName) continue;

      const normalizedKey = normalizeArabicText(trimmedName);
      if (!seen.has(normalizedKey)) {
        seen.add(normalizedKey);
        uniqueOptions.push({
          value: String(type.id),
          label: trimmedName,
          normalizedKey,
        });
      }
    }

    return [{ value: "all", label: "جميع أنواع الأنشطة" }, ...uniqueOptions];
  }, [rawActivityTypes]);

  // Load plans for branch
  const branchParams = useMemo(() => {
    const params = { all: true };
    const branchId = form.branch_id || selectedBranchId;
    if (branchId && branchId !== "all") {
      params.branch_id = branchId;
    }
    return params;
  }, [form.branch_id, selectedBranchId]);

  const { data: plansData, isLoading: isPlansLoading } = useGetSubscriptionPlansQuery(branchParams);

  const allPlans = useMemo(() => {
    let list = [];
    if (Array.isArray(plansData?.data?.data)) list = plansData.data.data;
    else if (Array.isArray(plansData?.data)) list = plansData.data;
    else if (Array.isArray(plansData)) list = plansData;

    const branchId = form.branch_id || selectedBranchId;
    if (branchId && branchId !== "all") {
      list = list.filter((plan) => {
        const branchIds = getEntityBranchIds(plan);
        return branchIds.length === 0 || branchIds.includes(String(branchId));
      });
    }

    return list;
  }, [plansData, form.branch_id, selectedBranchId]);

  // Filter plans by chosen activity_type (using normalized match to cover duplicate type IDs)
  const filteredPlans = useMemo(() => {
    const normalizedSearch = normalizeArabicText(planSearchTerm);

    return allPlans.filter((plan) => {
      if (selectedActivityTypeId !== "all") {
        const selectedOpt = activityTypeOptions.find(
          (opt) => String(opt.value) === String(selectedActivityTypeId),
        );
        const targetKey = selectedOpt?.normalizedKey;

        const matchesActivityType =
          // Match by ID directly
          (Array.isArray(plan.activity_types) &&
            plan.activity_types.some(
              (at) =>
                String(at.id) === String(selectedActivityTypeId) ||
                (targetKey &&
                  normalizeArabicText(formatLocalizedName(at.name) || at.name) === targetKey),
            )) ||
          // Match by plan activities
          (Array.isArray(plan.activities) &&
            plan.activities.some((act) => {
              const typeId = act.activity_type_id || act.activity_type?.id;
              const typeName =
                formatLocalizedName(act.activity_type?.name) || act.activity_type?.name;
              return (
                String(typeId) === String(selectedActivityTypeId) ||
                (targetKey && normalizeArabicText(typeName) === targetKey)
              );
            })) ||
          // Single activity_type on plan
          (plan.activity_type &&
            (String(plan.activity_type.id) === String(selectedActivityTypeId) ||
              (targetKey &&
                normalizeArabicText(
                  formatLocalizedName(plan.activity_type.name) || plan.activity_type.name,
                ) === targetKey)));

        if (!matchesActivityType) return false;
      }

      if (normalizedSearch) {
        const searchableText = [
          formatLocalizedName(plan.name) || plan.name,
          formatLocalizedName(plan.activity_type?.name) || plan.activity_type?.name,
          ...(plan.activity_types || []).map(
            (activityType) => formatLocalizedName(activityType?.name) || activityType?.name,
          ),
          ...(plan.activities || []).flatMap((activity) => [
            formatLocalizedName(activity?.name) || activity?.name,
            formatLocalizedName(activity?.activity_type?.name) || activity?.activity_type?.name,
          ]),
        ]
          .filter(Boolean)
          .join(" ");

        if (!normalizeArabicText(searchableText).includes(normalizedSearch)) return false;
      }

      return true;
    });
  }, [allPlans, selectedActivityTypeId, activityTypeOptions, planSearchTerm]);

  function updateField(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
    if (errors[key]) {
      setErrors((prev) => ({ ...prev, [key]: null }));
    }
  }

  function togglePlan(planId) {
    const idNum = Number(planId);
    setForm((prev) => {
      const exists = prev.plans.includes(idNum);
      const newPlans = exists ? prev.plans.filter((id) => id !== idNum) : [...prev.plans, idNum];
      return { ...prev, plans: newPlans };
    });
    if (errors.plans) {
      setErrors((prev) => ({ ...prev, plans: null }));
    }
  }

  function removePlan(planId) {
    const idNum = Number(planId);
    setForm((prev) => ({
      ...prev,
      plans: prev.plans.filter((id) => id !== idNum),
    }));
  }

  // Auto-generate offer name based on selected activities
  const suggestedName = useMemo(() => {
    if (!form.plans || form.plans.length === 0) return "";
    const selectedPlanObjects = form.plans
      .map((id) => allPlans.find((p) => Number(p.id) === Number(id)))
      .filter(Boolean);

    const planNames = selectedPlanObjects.map((p) => formatLocalizedName(p.name) || p.name);
    if (planNames.length === 0) return "";

    if (form.offer_type === "bundle") {
      return planNames.join(" + ");
    } else {
      const selectedTypeOpt = activityTypeOptions.find(
        (t) => String(t.value) === String(selectedActivityTypeId),
      );
      if (selectedTypeOpt && selectedActivityTypeId !== "all") {
        return `عرض ${selectedTypeOpt.label}`;
      }
      return `عرض ${planNames.join(" / ")}`;
    }
  }, [form.plans, form.offer_type, allPlans, activityTypeOptions, selectedActivityTypeId]);

  // Sync suggested name to form if name hasn't been manually edited
  useEffect(() => {
    if (isNameManuallyEdited || !suggestedName) return;
    setForm((prev) => ({ ...prev, name: suggestedName }));
    setErrors((prev) => {
      if (!prev.name) return prev;
      const next = { ...prev };
      delete next.name;
      return next;
    });
  }, [suggestedName, isNameManuallyEdited]);

  // Selected plans detailed records
  const selectedPlanRecords = useMemo(() => {
    return form.plans
      .map((id) => allPlans.find((p) => Number(p.id) === Number(id)))
      .filter(Boolean);
  }, [form.plans, allPlans]);

  // Calculate available subscriber capacity for this offer
  // Business rules:
  // 1. Bundle (باقة):
  //    - If unlimited + limited together -> capacity is the limited activity's capacity
  //    - If multiple limited activities -> capacity is min(...) across limited activities
  //    - If all are unlimited -> "غير محدود"
  // 2. Single choice (خصم على فعالية واحدة):
  //    - Do NOT take min!
  //    - We take the available seats for EACH activity separately, plus the total across activities
  const offerAvailableCapacity = useMemo(() => {
    if (selectedPlanRecords.length === 0) return null;

    const isPlanUnlimited = (p) => {
      const max = Number(p.max_subscribers);
      return Boolean(p.is_unlimited_subscribers || !p.max_subscribers || isNaN(max) || max === 0);
    };

    const getPlanSlots = (p) => {
      if (p.available_slots !== undefined && p.available_slots !== null) {
        return Math.max(0, Number(p.available_slots));
      }
      const max = Number(p.max_subscribers) || 0;
      const cur = Number(p.current_subscribers) || 0;
      return Math.max(0, max - cur);
    };

    if (form.offer_type === "bundle") {
      const limitedPlans = selectedPlanRecords.filter((p) => !isPlanUnlimited(p));

      if (limitedPlans.length === 0) {
        return {
          type: "bundle",
          isUnlimited: true,
          slots: Infinity,
          label: "غير محدود",
        };
      }

      const minSlots = Math.min(...limitedPlans.map(getPlanSlots));

      if (minSlots <= 0) {
        return {
          type: "bundle",
          isUnlimited: false,
          slots: 0,
          label: "0 مقعد (مكتمل العدد)",
        };
      }

      return {
        type: "bundle",
        isUnlimited: false,
        slots: minSlots,
        label: `${minSlots} مقعد متاح`,
        note: "(محسوب بأقل فعالية سعة في الباقة min، والفعالية غير المحدودة لا تقيّد الباقة)",
      };
    } else {
      // single_choice:
      // "وقت الخصم عفعالية وحدة مامناخد الmin مناخد المقاعد المتاحة لكل فعالية"
      let hasUnlimited = false;
      let totalSlots = 0;

      const perPlan = selectedPlanRecords.map((p) => {
        const unlimited = isPlanUnlimited(p);
        const slots = getPlanSlots(p);
        if (unlimited) {
          hasUnlimited = true;
        } else {
          totalSlots += slots;
        }
        return {
          id: p.id,
          name: formatLocalizedName(p.name) || p.name,
          isUnlimited: unlimited,
          slots: slots,
          max: Number(p.max_subscribers) || 0,
        };
      });

      const allFull = !hasUnlimited && totalSlots <= 0;

      return {
        type: "single_choice",
        isUnlimited: hasUnlimited,
        slots: totalSlots,
        label: hasUnlimited
          ? "متاح (يوجد فعاليات غير محدودة)"
          : allFull
            ? "0 مقعد (جميع الفعاليات مكتملة)"
            : `${totalSlots} مقعد متاح إجمالاً`,
        perPlan,
      };
    }
  }, [selectedPlanRecords, form.offer_type]);

  // Pricing calculations
  const isSingleChoice = form.offer_type === "single_choice";
  const planCount = selectedPlanRecords.length;

  const planPrices = useMemo(() => {
    return selectedPlanRecords.map((p) => Number(p.base_price ?? p.price) || 0);
  }, [selectedPlanRecords]);

  const totalRegularPrice = useMemo(() => {
    return planPrices.reduce((sum, val) => sum + val, 0);
  }, [planPrices]);

  const minRegularPrice = useMemo(() => {
    return planPrices.length > 0 ? Math.min(...planPrices) : 0;
  }, [planPrices]);

  const maxRegularPrice = useMemo(() => {
    return planPrices.length > 0 ? Math.max(...planPrices) : 0;
  }, [planPrices]);

  const avgRegularPrice = useMemo(() => {
    return planCount > 0 ? totalRegularPrice / planCount : 0;
  }, [totalRegularPrice, planCount]);

  const offerPriceNum = Number(form.price) || 0;

  // Single choice savings (per activity)
  const singleChoiceSavingsMin =
    minRegularPrice > offerPriceNum ? minRegularPrice - offerPriceNum : 0;
  const singleChoiceSavingsMax =
    maxRegularPrice > offerPriceNum ? maxRegularPrice - offerPriceNum : 0;
  const singleChoiceDiscountPercent =
    avgRegularPrice > 0 && singleChoiceSavingsMin > 0
      ? Math.round(((avgRegularPrice - offerPriceNum) / avgRegularPrice) * 100)
      : 0;

  // Bundle savings (total package savings & per-activity savings)
  const bundleTotalSavings =
    totalRegularPrice > offerPriceNum ? totalRegularPrice - offerPriceNum : 0;
  const bundleSavingsPerPlan =
    planCount > 0 && bundleTotalSavings > 0 ? Math.round(bundleTotalSavings / planCount) : 0;
  const bundleDiscountPercent =
    totalRegularPrice > 0 && bundleTotalSavings > 0
      ? Math.round((bundleTotalSavings / totalRegularPrice) * 100)
      : 0;

  function handleSubmit(e) {
    e.preventDefault();

    const branchId = form.branch_id || selectedBranchId || branches[0]?.id;

    const validation = offerSchema.safeParse({
      branch_id: branchId ? Number(branchId) : undefined,
      name: form.name.trim(),
      description: form.description ? form.description.trim() : undefined,
      offer_type: form.offer_type,
      price: form.price,
      start_date: !isUnlimitedDuration && form.start_date ? form.start_date : undefined,
      end_date: !isUnlimitedDuration && form.end_date ? form.end_date : undefined,
      is_active: form.is_active,
      plans: form.plans,
    });

    if (!validation.success) {
      const fieldErrors = {};
      validation.error.issues.forEach((issue) => {
        const key = issue.path[0];
        if (key) fieldErrors[key] = issue.message;
      });
      setErrors(fieldErrors);
      return;
    }

    setErrors({});
    onSubmit(validation.data);
  }

  return (
    <form
      id={formId}
      onSubmit={handleSubmit}
      className="grid items-start gap-5 text-right xl:grid-cols-[minmax(0,1fr)_320px]"
      dir="rtl"
      noValidate
    >
      <div className="space-y-5">
        <OfferFormSection
          number="١"
          title="نوع العرض والفعاليات"
          description="اختر آلية العرض ثم أضف خطط الاشتراك التي سيشملها."
        >
          <div className="grid gap-4 md:grid-cols-2">
            {/* 1. نوع العرض من قائمة منسدلة */}
            <label className="block text-right text-sm text-app-muted-light">
              نوع العرض *
              <Dropdown
                className="mt-2 text-white"
                buttonClassName="bg-app-card-soft h-11"
                value={form.offer_type}
                onChange={(val) => updateField("offer_type", val)}
                options={OFFER_TYPE_OPTIONS}
                placeholder="اختر نوع العرض"
                error={errors.offer_type}
              />
            </label>

            {/* 2. نوع النشاط */}
            <label className="block text-right text-sm text-app-muted-light">
              نوع النشاط *
              <Dropdown
                className="mt-2 text-white"
                buttonClassName="bg-app-card-soft h-11"
                value={selectedActivityTypeId}
                onChange={(val) => setSelectedActivityTypeId(val)}
                options={activityTypeOptions}
                placeholder="اختر نوع النشاط"
              />
            </label>
          </div>

          {/* 3. النشاط / الفعاليات */}
          <div className="space-y-2 pt-1">
            <div className="flex flex-wrap items-center justify-between gap-2">
              <span className="text-sm text-app-muted-light">الفعاليات المشمولة في العرض *</span>
              {form.plans.length > 0 && (
                <span className="text-xs text-app-yellow font-medium">
                  تم تحديد {form.plans.length} فعالية
                </span>
              )}
            </div>

            <SearchInput
              value={planSearchTerm}
              onChange={(event) => setPlanSearchTerm(event.target.value)}
              placeholder="ابحث باسم الفعالية أو نوع النشاط..."
              className="max-w-full"
            />

            {errors.plans && <p className="text-xs text-app-red">{errors.plans}</p>}

            {isPlansLoading ? (
              <p className="py-4 text-center text-xs text-app-muted-light">
                جاري تحميل الفعاليات...
              </p>
            ) : filteredPlans.length === 0 ? (
              <div className="rounded-lg border border-dashed border-app-line p-4 text-center text-xs text-app-muted-light">
                {planSearchTerm.trim()
                  ? "لا توجد فعاليات مطابقة لعملية البحث الحالية."
                  : selectedActivityTypeId !== "all"
                    ? "لا توجد فعاليات مطابقة لنوع النشاط المحدد في هذا الفرع."
                    : "لا توجد فعاليات مسجلة لهذا الفرع."}
              </div>
            ) : (
              <div className="max-h-56 overflow-y-auto space-y-1.5 rounded-lg border border-app-line bg-app-card-soft/40 p-2">
                {filteredPlans.map((plan) => {
                  const isSelected = form.plans.includes(Number(plan.id));
                  const planTypeName =
                    plan.activity_types?.[0]?.name ||
                    plan.activities?.[0]?.activity_type?.name ||
                    plan.activity_type?.name;

                  const isUnlimited = Boolean(
                    plan.is_unlimited_subscribers ||
                    !plan.max_subscribers ||
                    Number(plan.max_subscribers) === 0,
                  );
                  const slots =
                    plan.available_slots !== undefined && plan.available_slots !== null
                      ? Number(plan.available_slots)
                      : Math.max(
                          0,
                          (Number(plan.max_subscribers) || 0) -
                            (Number(plan.current_subscribers) || 0),
                        );

                  return (
                    <div
                      key={plan.id}
                      onClick={() => togglePlan(plan.id)}
                      className={`flex items-center justify-between p-2.5 rounded-lg border cursor-pointer transition-colors ${
                        isSelected
                          ? "border-app-yellow/80 bg-app-yellow/10 text-white"
                          : "border-app-line/60 bg-app-card-soft/60 text-app-muted-light hover:border-app-line hover:text-white"
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <div onClick={(event) => event.stopPropagation()}>
                          <Checkbox
                            checked={isSelected}
                            onChange={() => togglePlan(plan.id)}
                            aria-label={`تحديد ${formatLocalizedName(plan.name) || plan.name}`}
                          />
                        </div>
                        <div className="text-right">
                          <div className="flex items-center gap-2">
                            <span className="text-xs font-medium text-white">
                              {formatLocalizedName(plan.name) || plan.name}
                            </span>
                            {planTypeName && (
                              <span className="rounded bg-white/10 px-2 py-0.5 text-[10px] text-app-muted-light">
                                {formatLocalizedName(planTypeName) || planTypeName}
                              </span>
                            )}
                          </div>
                          <div className="mt-0.5">
                            {isUnlimited ? (
                              <span className="text-[10px] text-app-muted-light">
                                المقاعد: غير محدود
                              </span>
                            ) : slots <= 0 ? (
                              <span className="text-[10px] text-app-red font-medium">
                                مكتمل العدد (0/{plan.max_subscribers})
                              </span>
                            ) : (
                              <span className="text-[10px] text-emerald-400 font-medium">
                                المقاعد المتاحة: {slots} من {plan.max_subscribers}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                      <span className="text-xs font-semibold text-app-yellow">
                        {formatMoney(plan.base_price ?? plan.price)}
                      </span>
                    </div>
                  );
                })}
              </div>
            )}

            {/* Selected plans pills */}
            {selectedPlanRecords.length > 0 && (
              <div className="flex flex-wrap gap-1.5 pt-1">
                {selectedPlanRecords.map((plan) => (
                  <span
                    key={plan.id}
                    className="inline-flex items-center gap-1 rounded-md border border-app-line bg-app-card-soft px-2.5 py-1 text-xs text-white"
                  >
                    <span>{formatLocalizedName(plan.name) || plan.name}</span>
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        removePlan(plan.id);
                      }}
                      className="ms-1 text-app-muted-light hover:text-app-red text-sm leading-none"
                    >
                      ×
                    </button>
                  </span>
                ))}
              </div>
            )}
          </div>
        </OfferFormSection>

        <OfferFormSection
          number="٢"
          title="تفاصيل العرض والتسعير"
          description="اكتب اسمًا واضحًا وحدد السعر النهائي الذي سيظهر للمشترك."
        >
          {/* 4. اسم العرض هو اسم الفعاليات يلي اخترتن */}
          <Field
            label="اسم العرض"
            value={form.name}
            onChange={(e) => {
              setIsNameManuallyEdited(true);
              updateField("name", e.target.value);
            }}
            placeholder={suggestedName || "اسم الفعاليات المختارة"}
            required
            error={errors.name}
          />

          <TextAreaField
            label="وصف العرض"
            value={form.description}
            onChange={(event) => updateField("description", event.target.value)}
            placeholder="اكتب وصفًا مختصرًا يوضح مزايا العرض وشروطه"
            error={errors.description}
          />

          {/* 5. سعر العرض */}
          <div>
            <Field
              label="سعر العرض"
              type="number"
              min="0"
              step="any"
              value={form.price}
              onChange={(e) => updateField("price", e.target.value)}
              placeholder="أدخل سعر العرض"
              required
              error={errors.price}
            />
            {planCount > 0 && offerPriceNum > 0 && (
              <div className="rounded-lg border border-app-line/70 bg-app-card-soft/90 p-2.5 text-xs space-y-1.5 mt-2">
                {isSingleChoice ? (
                  // Single choice savings display (per single activity)
                  <div className="space-y-1">
                    <div className="flex items-center justify-between text-app-muted-light">
                      <span>السعر الأصلي للفعالية:</span>
                      <strong className="text-white">
                        {minRegularPrice === maxRegularPrice
                          ? formatMoney(minRegularPrice)
                          : `${formatMoney(minRegularPrice)} - ${formatMoney(maxRegularPrice)}`}
                      </strong>
                    </div>

                    {singleChoiceSavingsMin > 0 ? (
                      <div className="flex items-center justify-between text-emerald-400 font-medium border-t border-app-line/50 pt-1">
                        <span>توفير المشتركة (للفعالية الواحدة):</span>
                        <span>
                          {minRegularPrice === maxRegularPrice
                            ? `${formatMoney(singleChoiceSavingsMin)} (خصم ${singleChoiceDiscountPercent}%)`
                            : `${formatMoney(singleChoiceSavingsMin)} - ${formatMoney(singleChoiceSavingsMax)}`}
                        </span>
                      </div>
                    ) : (
                      <div className="text-app-muted-light text-[11px] border-t border-app-line/50 pt-1">
                        سعر العرض مساوٍ أو أكبر من السعر الأصلي للفعالية.
                      </div>
                    )}
                  </div>
                ) : (
                  // Bundle savings display (shows both total savings & per-activity savings)
                  <div className="space-y-1">
                    <div className="flex items-center justify-between text-app-muted-light">
                      <span>مجموع السعر الأصلي للفعاليات:</span>
                      <strong className="text-white">{formatMoney(totalRegularPrice)}</strong>
                    </div>

                    {bundleTotalSavings > 0 ? (
                      <>
                        <div className="flex items-center justify-between text-emerald-400 font-medium border-t border-app-line/50 pt-1">
                          <span>مجموع التوفير في الباقة:</span>
                          <span>
                            {formatMoney(bundleTotalSavings)} (خصم {bundleDiscountPercent}%)
                          </span>
                        </div>

                        {planCount > 1 && (
                          <div className="flex items-center justify-between text-app-muted-light text-[11px]">
                            <span>متوسط التوفير لكل فعالية في الباقة:</span>
                            <span className="text-white font-medium">
                              {formatMoney(bundleSavingsPerPlan)}
                            </span>
                          </div>
                        )}
                      </>
                    ) : (
                      <div className="text-app-muted-light text-[11px] border-t border-app-line/50 pt-1">
                        سعر الباقة مساوٍ أو أكبر من مجموع أسعار الفعاليات الأصلية.
                      </div>
                    )}
                  </div>
                )}
              </div>
            )}
          </div>

          {/* عدد المشتركين المتاح تسجيلهم لهذا العرض */}
          <div className="rounded-xl border border-app-line bg-app-card-soft/80 p-3.5 text-xs space-y-2">
            <div className="flex items-center justify-between">
              <span className="text-app-muted-light font-medium">
                {form.offer_type === "single_choice"
                  ? "المقاعد المتاحة لفعاليات هذا العرض:"
                  : "عدد المشتركين المتاح تسجيلهم لهذا العرض:"}
              </span>
              <span
                className={`font-bold text-sm ${
                  !offerAvailableCapacity
                    ? "text-app-muted-light"
                    : offerAvailableCapacity.slots === 0 && !offerAvailableCapacity.isUnlimited
                      ? "text-app-red"
                      : "text-emerald-400"
                }`}
              >
                {offerAvailableCapacity
                  ? offerAvailableCapacity.label
                  : "يُحسب تلقائياً حسب الفعاليات المحددة"}
              </span>
            </div>

            {/* In single_choice: show the breakdown of available seats for each individual activity */}
            {offerAvailableCapacity?.type === "single_choice" && offerAvailableCapacity.perPlan && (
              <div className="border-t border-app-line/60 pt-2 space-y-1.5">
                <span className="text-[11px] text-app-muted-light block">
                  المقاعد المتاحة لكل فعالية:
                </span>
                <div className="flex flex-wrap gap-2">
                  {offerAvailableCapacity.perPlan.map((planItem) => (
                    <span
                      key={planItem.id}
                      className={`inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs border ${
                        planItem.isUnlimited
                          ? "border-app-line bg-app-card-soft text-white"
                          : planItem.slots <= 0
                            ? "border-app-red/40 bg-app-red/10 text-app-red"
                            : "border-emerald-500/30 bg-emerald-500/10 text-emerald-300"
                      }`}
                    >
                      <span className="font-medium">{planItem.name}:</span>
                      <span className="font-bold">
                        {planItem.isUnlimited ? "غير محدود" : `${planItem.slots} مقعد`}
                      </span>
                    </span>
                  ))}
                </div>
              </div>
            )}

            {offerAvailableCapacity?.type === "bundle" && !offerAvailableCapacity.isUnlimited && (
              <p className="text-[11px] text-app-muted-light">
                (في الباقة: تعتمد السعة على الفعالية المحدودة، أو الفعالية الأقل مقاعد min عند وجود
                أكثر من فعالية محدودة)
              </p>
            )}
          </div>
        </OfferFormSection>

        <OfferFormSection
          number="٣"
          title="مدة الإتاحة والنشر"
          description="حدد فترة العرض وحالته قبل نشره للاشتراكات."
        >
          {/* تواريخ العرض */}
          <div className="space-y-2.5 pt-1">
            <CheckboxField
              label="فعالية غير محدودة"
              checked={isUnlimitedDuration}
              onChange={(e) => {
                const checked = e.target.checked;
                setIsUnlimitedDuration(checked);
                if (checked) {
                  updateField("start_date", "");
                  updateField("end_date", "");
                }
              }}
            />

            {!isUnlimitedDuration && (
              <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 pt-1">
                <Field
                  label="من تاريخ"
                  type="date"
                  required={false}
                  value={form.start_date}
                  onChange={(e) => updateField("start_date", e.target?.value ?? e)}
                  error={errors.start_date}
                />
                <Field
                  label="إلى تاريخ"
                  type="date"
                  required={false}
                  value={form.end_date}
                  onChange={(e) => updateField("end_date", e.target?.value ?? e)}
                  error={errors.end_date}
                />
              </div>
            )}
          </div>

          {/* 6. هل العرض نشط أو لا */}
          <label className="flex items-center gap-3 pt-2">
            <input
              type="checkbox"
              checked={form.is_active}
              onChange={(event) => updateField("is_active", event.target.checked)}
              className="peer sr-only"
            />
            <span className="relative h-6 w-11 cursor-pointer rounded-full bg-app-line after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:transition-all peer-checked:bg-app-yellow peer-checked:after:-translate-x-[18px]" />
            <span className="text-sm font-medium text-white">نشط في النظام</span>
          </label>
        </OfferFormSection>
      </div>

      <aside className="space-y-4 xl:sticky xl:top-6">
        <section className="app-card overflow-hidden rounded-2xl">
          <header className="border-b border-app-line px-5 py-4">
            <p className="text-base font-semibold text-app-text">ملخص العرض</p>
            <p className="mt-1 text-xs text-app-muted-light">تحديث مباشر حسب البيانات المدخلة</p>
          </header>

          <div className="space-y-4 p-5">
            <div className="rounded-xl border border-app-yellow/25 bg-app-yellow-soft p-4 text-center">
              <p className="text-xs text-app-muted-light">السعر النهائي</p>
              <p className="mt-2 text-2xl font-bold text-app-yellow">
                {offerPriceNum > 0 ? formatMoney(offerPriceNum) : "—"}
              </p>
            </div>

            <dl className="space-y-3 text-sm">
              <div className="flex items-center justify-between gap-3 border-b border-app-line/70 pb-3">
                <dt className="text-app-muted-light">نوع العرض</dt>
                <dd className="font-medium text-app-text">
                  {isSingleChoice ? "اختيار فعالية" : "باقة مجمعة"}
                </dd>
              </div>
              <div className="flex items-center justify-between gap-3 border-b border-app-line/70 pb-3">
                <dt className="text-app-muted-light">الفعاليات المحددة</dt>
                <dd className="font-medium text-app-text">{planCount.toLocaleString("ar")}</dd>
              </div>
              <div className="flex items-start justify-between gap-3">
                <dt className="shrink-0 text-app-muted-light">السعة المتاحة</dt>
                <dd className="text-left text-xs font-medium leading-5 text-emerald-400">
                  {!offerAvailableCapacity
                    ? "تُحسب بعد اختيار الفعاليات"
                    : offerAvailableCapacity.isUnlimited
                      ? "إتاحة مفتوحة"
                      : `${offerAvailableCapacity.slots.toLocaleString("ar")} مقعد`}
                </dd>
              </div>
            </dl>

            {selectedPlanRecords.length > 0 && (
              <div className="border-t border-app-line pt-4">
                <p className="mb-2 text-xs font-medium text-app-muted-light">محتويات العرض</p>
                <ul className="space-y-2">
                  {selectedPlanRecords.map((plan) => (
                    <li
                      key={plan.id}
                      className="flex items-center gap-2 text-xs text-app-text before:size-1.5 before:shrink-0 before:rounded-full before:bg-app-yellow"
                    >
                      <span className="truncate">
                        {formatLocalizedName(plan.name) || plan.name}
                      </span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        </section>

        {/* رسالة الخطأ إن وجدت */}
        {errorMessage && (
          <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
            {errorMessage}
          </p>
        )}

        {/* أزرار الحفظ والإلغاء */}
        {showFooterActions && (
          <div className="app-card flex gap-3 rounded-2xl p-4">
            <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
              إلغاء
            </Button>
            <Button type="submit" className="h-11 flex-1" loading={isLoading}>
              {mode === "edit" ? "حفظ التعديل" : "إنشاء العرض"}
            </Button>
          </div>
        )}
      </aside>
    </form>
  );
}
