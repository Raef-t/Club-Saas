"use client";

import { useState, useMemo, useCallback } from "react";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import { CURRENCY_SYMBOL, formatLocalizedName, formatMoney } from "@/lib/utils";
import { subscriptionEditSchema, subscriptionSchema } from "@/lib/validations/subscriptionsSchema";
import {
  getLocalDateValue,
  getSubscriptionSplitPaymentAmounts,
  getSubscriptionReceiptNumbers,
  getSubscriptionEndDate,
  isDailyEntrySubscriptionPlan,
  isPrivateSubscriptionPlan,
} from "./subscriptionUtils";
import { SUBSCRIPTION_STATUS_OPTIONS } from "./subscriptionConstants";
import { getMemberAccountName } from "@/lib/memberIdentity";

/**
 * Collects and validates the values required to create a member subscription.
 */
export function SubscriptionCreateForm({
  members = [],
  plans = [],
  activityTypes = [],
  selectedActivityTypeId = "",
  onActivityTypeChange,
  isPlansLoading = false,
  plansErrorMessage = "",
  isActivityTypesLoading = false,
  activityTypesErrorMessage = "",
  activities = [],
  coaches = [],
  offers = [],
  isOffersLoading = false,
  onSubmit,
  onCancel,
  isLoading,
  errorMessage,
  formId,
  showFooterActions = true,
  formClassName = "space-y-4",
  initialMemberId = "",
  lockMemberId = false,
  submitLabel,
  cancelLabel,
  showAddAnother = true,
}) {
  // Check if a plan matches selectedActivityTypeId
  const isPlanMatchingActivityType = useCallback(
    (plan) => {
      if (!selectedActivityTypeId || selectedActivityTypeId === "all") return true;
      if (
        Array.isArray(plan.activity_types) &&
        plan.activity_types.some((at) => String(at.id) === String(selectedActivityTypeId))
      ) {
        return true;
      }
      if (
        Array.isArray(plan.activities) &&
        plan.activities.some((act) => String(act.activity_type_id) === String(selectedActivityTypeId))
      ) {
        return true;
      }
      const fullPlan = plans.find((pl) => String(pl.id) === String(plan.id));
      if (fullPlan) {
        if (
          Array.isArray(fullPlan.activity_types) &&
          fullPlan.activity_types.some((at) => String(at.id) === String(selectedActivityTypeId))
        ) {
          return true;
        }
        if (
          Array.isArray(fullPlan.activities) &&
          fullPlan.activities.some((act) => String(act.activity_type_id) === String(selectedActivityTypeId))
        ) {
          return true;
        }
      }
      return false;
    },
    [selectedActivityTypeId, plans],
  );

  // Filter offers by selectedActivityTypeId and availability
  const availableOffers = useMemo(() => {
    if (!Array.isArray(offers) || offers.length === 0) return [];
    return offers.filter((offer) => {
      if (!offer.is_available) return false;
      if (selectedActivityTypeId && selectedActivityTypeId !== "all") {
        const offerPlans = offer.plans || [];
        return offerPlans.some(isPlanMatchingActivityType);
      }
      return true;
    });
  }, [offers, selectedActivityTypeId, isPlanMatchingActivityType]);

  // Options for offers dropdown:
  // For bundle: 1 option for the bundle
  // For single_choice: expand each eligible activity as a direct offer choice ("نعامل كل فعالية كانها عرض")
  const offerOptions = useMemo(() => {
    const options = [{ value: "", label: "بدون عرض (اشتراك فردي اعتيادي)" }];

    availableOffers.forEach((offer) => {
      if (offer.offer_type === "single_choice") {
        const eligiblePlans = (offer.plans || []).filter(isPlanMatchingActivityType);
        eligiblePlans.forEach((plan) => {
          const planName = formatLocalizedName(plan.name) || plan.name || "";
          const isFull = plan.max_subscribers > 0 && plan.current_subscribers >= plan.max_subscribers;

          const offerTitle = offer.name.startsWith("عرض") ? offer.name : `عرض ${offer.name}`;
          const displayName = offerTitle.includes(planName)
            ? offerTitle
            : `${offerTitle} - ${planName}`;

          options.push({
            value: `offer_${offer.id}_plan_${plan.id}`,
            offerId: String(offer.id),
            planId: String(plan.id),
            isBundle: false,
            label: `${displayName} - ${formatMoney(offer.price)}${isFull ? " (⚠️ مكتملة السعة)" : ""}`,
            disabled: isFull,
          });
        });
      } else {
        // bundle offer
        options.push({
          value: `offer_${offer.id}`,
          offerId: String(offer.id),
          planId: null,
          isBundle: true,
          label: `${offer.name} - ${formatMoney(offer.price)} (باقة مجمعة)`,
        });
      }
    });

    return options;
  }, [availableOffers, isPlanMatchingActivityType]);

  const [form, setForm] = useState(() => {
    const initialPlan = plans[0] || null;
    const initialDate = isDailyEntrySubscriptionPlan(initialPlan) ? getLocalDateValue() : "";

    return {
      member_id: initialMemberId
        ? String(initialMemberId)
        : members[0]?.id
          ? String(members[0].id)
          : "",
      offer_id: "",
      plan_id: initialPlan?.id ? String(initialPlan.id) : "",
      paid_amount: initialPlan?.base_price ? String(initialPlan.base_price) : "0",
      months_count: "1",
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: initialDate,
      end_date: initialDate,
    };
  });
  const [errors, setErrors] = useState({});
  const [submitAction, setSubmitAction] = useState("normal");
  const selectedOfferObj = useMemo(() => {
    return availableOffers.find((o) => String(o.id) === String(form.offer_id));
  }, [availableOffers, form.offer_id]);

  const isSelectedOfferSingleChoice = selectedOfferObj?.offer_type === "single_choice";

  const matchingSingleChoiceOffer = useMemo(() => {
    if (form.offer_id || !form.plan_id) return null;
    return availableOffers.find((offer) => {
      if (offer.offer_type !== "single_choice" || !offer.is_available) return false;
      return (offer.plans || []).some((p) => String(p.id) === String(form.plan_id));
    });
  }, [availableOffers, form.offer_id, form.plan_id]);

  const selectedPlanObj = useMemo(() => {
    if (form.offer_id && isSelectedOfferSingleChoice && selectedOfferObj?.plans) {
      return (
        selectedOfferObj.plans.find((p) => String(p.id) === String(form.plan_id)) ||
        plans.find((p) => String(p.id) === String(form.plan_id))
      );
    }
    return plans.find((p) => String(p.id) === String(form.plan_id));
  }, [plans, form.plan_id, form.offer_id, isSelectedOfferSingleChoice, selectedOfferObj]);

  const selectedActivityType = activityTypes.find(
    (activityType) => String(activityType.id) === String(selectedActivityTypeId),
  );
  const isDailyEntryPlan = !form.offer_id && isDailyEntrySubscriptionPlan(selectedPlanObj);
  const isPrivatePlan = !form.offer_id && isPrivateSubscriptionPlan(selectedPlanObj, selectedActivityType);

  function updateField(field, value) {
    setForm((current) => {
      const nextState = { ...current, [field]: value };
      if (!isDailyEntryPlan && (field === "start_date" || field === "months_count")) {
        const startDate = field === "start_date" ? value : current.start_date;
        const months = field === "months_count" ? value : current.months_count;
        if (startDate) nextState.end_date = getSubscriptionEndDate(startDate, months);
      }
      return nextState;
    });
    if (errors && errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  const selectedOfferDropdownValue = useMemo(() => {
    if (!form.offer_id) return "";
    if (isSelectedOfferSingleChoice) {
      if (form.plan_id) {
        const exact = `offer_${form.offer_id}_plan_${form.plan_id}`;
        if (offerOptions.some((o) => o.value === exact)) return exact;
      }
      const match = offerOptions.find((o) => o.offerId === String(form.offer_id));
      return match ? match.value : "";
    }
    const bundleVal = `offer_${form.offer_id}`;
    if (offerOptions.some((o) => o.value === bundleVal)) return bundleVal;
    return "";
  }, [form.offer_id, form.plan_id, isSelectedOfferSingleChoice, offerOptions]);

  function handleOfferOptionChange(val) {
    if (!val) {
      const defaultPlan = plans.find((p) => String(p.id) === String(form.plan_id)) || plans[0] || null;
      const today = isDailyEntrySubscriptionPlan(defaultPlan) ? getLocalDateValue() : "";
      setForm((current) => ({
        ...current,
        offer_id: "",
        plan_id: defaultPlan?.id ? String(defaultPlan.id) : "",
        paid_amount: defaultPlan?.base_price ? String(defaultPlan.base_price) : "0",
        start_date: today || current.start_date,
        end_date: today || current.end_date,
      }));
      setErrors((current) => ({ ...current, offer_id: null, plan_id: null, paid_amount: null }));
      return;
    }

    let opt = offerOptions.find((o) => o.value === String(val));
    if (!opt) {
      opt = offerOptions.find((o) => o.offerId === String(val));
    }
    if (!opt) return;

    const selectedOffer = availableOffers.find((o) => String(o.id) === String(opt.offerId));
    if (!selectedOffer) return;

    const offerPrice = String(selectedOffer.price ?? "0");
    const today = getLocalDateValue();

    if (selectedOffer.offer_type === "single_choice") {
      setForm((current) => ({
        ...current,
        offer_id: String(selectedOffer.id),
        plan_id: String(opt.planId),
        paid_amount: offerPrice,
        start_date: selectedOffer.start_date || current.start_date || today,
        end_date:
          selectedOffer.end_date ||
          current.end_date ||
          getSubscriptionEndDate(current.start_date || today, current.months_count || 1),
      }));
    } else {
      // bundle
      const offerPlans = selectedOffer.plans || [];
      const firstPlan = offerPlans[0];
      setForm((current) => ({
        ...current,
        offer_id: String(selectedOffer.id),
        plan_id: firstPlan?.id ? String(firstPlan.id) : current.plan_id || "1",
        paid_amount: offerPrice,
        start_date: selectedOffer.start_date || current.start_date || today,
        end_date:
          selectedOffer.end_date ||
          current.end_date ||
          getSubscriptionEndDate(current.start_date || today, current.months_count || 1),
      }));
    }

    setErrors((current) => ({
      ...current,
      offer_id: null,
      plan_id: null,
      paid_amount: null,
    }));
  }

  function handleOfferChange(offerId) {
    handleOfferOptionChange(offerId);
  }

  function handlePlanChange(planId) {
    const nextPlan = plans.find((plan) => String(plan.id) === String(planId));
    const currentPlan = plans.find((plan) => String(plan.id) === String(form.plan_id));
    const nextIsDailyEntry = isDailyEntrySubscriptionPlan(nextPlan);
    const currentIsDailyEntry = isDailyEntrySubscriptionPlan(currentPlan);
    const today = nextIsDailyEntry ? getLocalDateValue() : "";

    setForm((current) => ({
      ...current,
      offer_id: "",
      plan_id: planId,
      paid_amount: nextPlan ? String(nextPlan.base_price || "0") : current.paid_amount,
      months_count: nextIsDailyEntry ? "1" : current.months_count,
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.start_date,
      end_date: nextIsDailyEntry ? today : currentIsDailyEntry ? "" : current.end_date,
    }));

    setErrors((current) => ({
      ...current,
      offer_id: null,
      plan_id: null,
      paid_amount: null,
      months_count: null,
      receipt_number: null,
      coach_receipt_number: null,
      branch_receipt_number: null,
      start_date: null,
      end_date: null,
    }));
  }

  function handleActivityTypeChange(activityTypeId) {
    setForm((current) => ({
      ...current,
      offer_id: "",
      plan_id: "",
      paid_amount: "0",
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: isDailyEntryPlan ? "" : current.start_date,
      end_date: isDailyEntryPlan ? "" : current.end_date,
    }));
    setErrors((current) => ({ ...current, plan_id: null, paid_amount: null, offer_id: null }));
    onActivityTypeChange?.(activityTypeId);
  }

  function handleSubmit(event) {
    event.preventDefault();

    const isOfferSelected = Boolean(form.offer_id);
    const dailyEntryDate = isDailyEntryPlan ? getLocalDateValue() : "";

    if (isOfferSelected && isSelectedOfferSingleChoice && !form.plan_id) {
      setErrors((current) => ({
        ...current,
        plan_id: "يرجى اختيار الفعالية المراد الاشتراك بها ضمن العرض",
      }));
      return;
    }

    const validationData = {
      member_id: Number(form.member_id),
      plan_id: isOfferSelected ? Number(form.plan_id) || 1 : Number(form.plan_id),
      offer_id: isOfferSelected ? Number(form.offer_id) : undefined,
      paid_amount: Number(form.paid_amount) || 0,
      months_count: isDailyEntryPlan ? 1 : form.months_count,
      receipt_number: form.receipt_number,
      coach_receipt_number: form.coach_receipt_number,
      branch_receipt_number: form.branch_receipt_number,
      is_private_plan: isOfferSelected ? false : isPrivatePlan,
      start_date: dailyEntryDate || form.start_date || "",
      end_date: dailyEntryDate || form.end_date || "",
    };

    const result = subscriptionSchema.safeParse(validationData);
    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        formattedErrors[issue.path.join("_")] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(
      {
        ...result.data,
        offer_id: isOfferSelected ? Number(form.offer_id) : undefined,
        payment_method: "cash",
        activities: [],
      },
      submitAction,
    );
  }

  return (
    <form id={formId} noValidate onSubmit={handleSubmit} className={formClassName} dir="rtl">
      {lockMemberId ? (
        <div className="block text-right text-sm text-app-muted-light">
          <span>اللاعب العضو</span>
          <div className="mt-2 flex h-11 items-center rounded-xl bg-app-card-soft px-3 text-white opacity-75">
            {(() => {
              const m = members.find((m) => String(m.id) === String(form.member_id));
              if (!m) return "العضو المحدد";

              const memberName =
                m.person?.full_name ||
                `${m.first_name || ""} ${m.last_name || ""}`.trim() ||
                "عضو بدون اسم";
              const accountName = getMemberAccountName(m);

              return accountName ? `${memberName} (${accountName})` : memberName;
            })()}
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          اللاعب العضو
          <Dropdown
            searchable
            searchPlaceholder="ابحث عن اللاعب بالاسم..."
            className="mt-2 text-white"
            buttonClassName="bg-app-card-soft h-11"
            value={form.member_id}
            onChange={(val) => updateField("member_id", val)}
            options={members.map((m) => ({
              value: String(m.id),
              label:
                m.person?.full_name ||
                `${m.first_name || ""} ${m.last_name || ""}`.trim() ||
                "عضو بدون اسم",
            }))}
            placeholder="اختر اللاعب"
            error={errors && errors.member_id}
          />
        </label>
      )}

      <label className="block text-right text-sm text-app-muted-light">
        نوع النشاط
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="bg-app-card-soft h-11"
          value={selectedActivityTypeId}
          onChange={handleActivityTypeChange}
          options={[
            { value: "", label: "الكل" },
            ...activityTypes.map((activityType) => ({
              value: String(activityType.id),
              label: formatLocalizedName(activityType.name) || `نوع النشاط #${activityType.id}`,
            })),
          ]}
          placeholder={isActivityTypesLoading ? "جاري تحميل أنواع الأنشطة..." : "اختر نوع النشاط"}
          disabled={isActivityTypesLoading}
        />
        {activityTypesErrorMessage && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {activityTypesErrorMessage}
          </span>
        )}
      </label>

      {/* Offers Dropdown - Appears ONLY when offers are available matching the selected activity type */}
      {offerOptions.length > 1 && (
        <div className="rounded-xl border border-app-yellow/40 bg-app-yellow/[0.04] p-4 space-y-3">
          <label className="block text-right text-sm font-medium text-white">
            <span className="flex items-center justify-between">
              <span className="flex items-center gap-1.5">
                <span>🎁</span>
                <span>باقة العروض الترويجية (خصم خاص)</span>
              </span>
              <span className="text-xs text-app-yellow font-normal">
                ({offerOptions.length - 1} {offerOptions.length - 1 === 1 ? "عرض متاح" : "عروض متاحة"})
              </span>
            </span>
            <Dropdown
              className="mt-2 text-white"
              buttonClassName="bg-app-card-soft h-11 border-app-yellow/40"
              value={selectedOfferDropdownValue}
              onChange={handleOfferOptionChange}
              options={offerOptions}
              placeholder="اختر عرض ترويجي"
            />
          </label>

          {/* تفاصيل العرض المختار */}
          {form.offer_id && selectedOfferObj && (
            <div className="rounded-xl border border-app-line bg-black/40 p-4 space-y-3 text-right">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                  <span className="text-sm font-bold text-white">
                    {isSelectedOfferSingleChoice && selectedPlanObj
                      ? `${selectedOfferObj.name} - ${formatLocalizedName(selectedPlanObj.name) || selectedPlanObj.name}`
                      : selectedOfferObj.name}
                  </span>
                  <span
                    className={`rounded-md px-2 py-0.5 text-[10px] font-medium ${
                      isSelectedOfferSingleChoice
                        ? "bg-purple-500/20 text-purple-300 border border-purple-500/40"
                        : "bg-blue-500/20 text-blue-300 border border-blue-500/40"
                    }`}
                  >
                    {isSelectedOfferSingleChoice ? "🏷️ يختار المشترك فعالية واحدة" : "📦 باقة مجمعة"}
                  </span>
                </div>
                <span className="text-sm font-black text-app-yellow">
                  {formatMoney(selectedOfferObj.price)}
                </span>
              </div>

              {selectedOfferObj.description && (
                <p className="text-xs text-app-muted-light leading-relaxed">
                  {selectedOfferObj.description}
                </p>
              )}

              {isSelectedOfferSingleChoice ? (
                <div className="rounded-lg bg-purple-500/10 border border-purple-500/30 p-2.5 text-xs text-purple-200">
                  💡 تم تطبيق سعر العرض المخفض ({formatMoney(selectedOfferObj.price)}) على فعالية{" "}
                  <strong className="text-white">
                    {formatLocalizedName(selectedPlanObj?.name) || selectedPlanObj?.name}
                  </strong>
                  {selectedPlanObj?.base_price && Number(selectedPlanObj.base_price) > Number(selectedOfferObj.price) && (
                    <span className="ms-1 text-emerald-400 font-semibold">
                      (توفير {formatMoney(Number(selectedPlanObj.base_price) - Number(selectedOfferObj.price))})
                    </span>
                  )}
                </div>
              ) : (
                <div className="flex items-center gap-1.5 flex-wrap pt-1">
                  <span className="text-xs text-app-muted-light">الفعاليات المشمولة بالباقة:</span>
                  {(selectedOfferObj.plans || []).map((p) => (
                    <span
                      key={p.id}
                      className="rounded bg-app-card-soft border border-app-line/60 px-2 py-0.5 text-[11px] text-gray-200"
                    >
                      ● {p.name} {p.session_count ? `(${p.session_count} حصة)` : ""}
                    </span>
                  ))}
                </div>
              )}
            </div>
          )}
        </div>
      )}

      {/* خطة الاشتراك أو تأكيد العرض */}
      {form.offer_id && !isSelectedOfferSingleChoice ? (
        <div className="rounded-xl border border-app-yellow/40 bg-app-yellow/10 p-3.5 text-right text-xs text-app-muted-light space-y-1">
          <p className="text-white font-medium flex items-center gap-1.5">
            <span>📦</span>
            <span>تم اختيار باقة عرض ترويجي مجمعة ({selectedOfferObj?.name})</span>
          </p>
          <p className="text-white">
            سيتم تفعيل جميع خطط وفعاليات هذا العرض تلقائياً لحساب اللاعبة عند تأكيد الاشتراك.
          </p>
        </div>
      ) : form.offer_id && isSelectedOfferSingleChoice ? (
        <div className="rounded-xl border border-purple-500/30 bg-purple-500/10 p-3.5 text-right text-xs space-y-1">
          <div className="flex items-center justify-between">
            <span className="text-white font-medium flex items-center gap-1.5">
              <span>🏷️</span>
              <span>الفعالية المحددة بالعرض: <strong className="text-purple-200">{formatLocalizedName(selectedPlanObj?.name) || selectedPlanObj?.name}</strong></span>
            </span>
            <span className="text-app-yellow font-bold">
              {formatMoney(selectedOfferObj?.price)}
            </span>
          </div>
          {selectedPlanObj?.base_price && (
            <p className="text-app-muted-light text-[11px]">
              السعر الأساسي للفعالية: {formatMoney(selectedPlanObj.base_price)}
              {Number(selectedPlanObj.base_price) > Number(selectedOfferObj.price) && (
                <span className="text-emerald-400 font-semibold ms-1">
                  (توفير {formatMoney(Number(selectedPlanObj.base_price) - Number(selectedOfferObj.price))})
                </span>
              )}
            </p>
          )}
        </div>
      ) : (
        <div className="space-y-2">
          {matchingSingleChoiceOffer && (
            <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-3 text-right flex items-center justify-between gap-3 text-xs">
              <div className="space-y-0.5 min-w-0">
                <span className="font-bold text-emerald-300 block truncate">
                  🏷️ يتوفر عرض مخفض سارٍ على هذه الفعالية!
                </span>
                <span className="text-app-muted-light text-[11px] block">
                  يمكن للاعبة الاستفادة من <strong className="text-white">{matchingSingleChoiceOffer.name}</strong> ودفع{" "}
                  <strong className="text-app-yellow">{formatMoney(matchingSingleChoiceOffer.price)}</strong> بدلاً من {formatMoney(selectedPlanObj?.base_price)}.
                </span>
              </div>
              <Button
                type="button"
                tone="success"
                className="h-8 px-3 text-xs shrink-0 text-black font-semibold"
                onClick={() => handleOfferOptionChange(`offer_${matchingSingleChoiceOffer.id}_plan_${form.plan_id}`)}
              >
                تطبيق العرض
              </Button>
            </div>
          )}

          <label className="block text-right text-sm text-app-muted-light">
            خطة الاشتراك
            {isPlansLoading ? (
              <div
                className="mt-2 flex h-11 items-center justify-center gap-2 rounded-xl border border-app-line bg-app-card-soft text-xs text-app-muted-light"
                role="status"
              >
                <span className="size-4 animate-spin rounded-full border-2 border-app-muted border-t-app-yellow" />
                جاري تحميل باقات الاشتراك...
              </div>
            ) : (
              <Dropdown
                className="mt-2 text-white"
                buttonClassName="bg-app-card-soft h-11"
                value={form.plan_id}
                onChange={handlePlanChange}
                options={plans.map((p) => ({
                  value: String(p.id),
                  label: formatLocalizedName(p.name) || p.name || "",
                }))}
                placeholder="اختر الخطة"
                disabled={plans.length === 0 || Boolean(plansErrorMessage)}
                error={errors && errors.plan_id}
              />
            )}
            {plansErrorMessage ? (
              <span className="mt-1.5 block text-xs text-app-red" role="alert">
                {plansErrorMessage}
              </span>
            ) : (
              !isPlansLoading &&
              plans.length === 0 && (
                <span className="mt-1.5 block text-xs text-app-muted-light" role="status">
                  {selectedActivityTypeId
                    ? "لا توجد باقات اشتراك متاحة لنوع النشاط المحدد"
                    : "لا توجد باقات اشتراك متاحة حالياً"}
                </span>
              )
            )}
          </label>
        </div>
      )}

      <label className="block text-right text-sm text-app-muted-light">
        المبلغ المدفوع للاشتراك ({CURRENCY_SYMBOL})
        <input
          type="number"
          min="0"
          value={form.paid_amount}
          onChange={(e) => updateField("paid_amount", e.target.value)}
          aria-invalid={Boolean(errors && errors.paid_amount)}
          className={`app-input mt-2 h-11 w-full px-3 text-right outline-none bg-app-card-soft text-white ${
            errors && errors.paid_amount
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          placeholder={
            form.offer_id
              ? "سعر العرض الترويجي"
              : selectedPlanObj
                ? `السعر الأساسي: ${selectedPlanObj.base_price}`
                : ""
          }
          required
        />
        {errors && errors.paid_amount && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.paid_amount}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        عدد الأشهر *
        <input
          type="number"
          min="1"
          step="1"
          value={form.months_count}
          onChange={(event) => updateField("months_count", event.target.value)}
          disabled={isDailyEntryPlan}
          aria-invalid={Boolean(errors && errors.months_count)}
          className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:opacity-60 ${
            errors && errors.months_count
              ? "border border-app-red focus:border-app-red"
              : "focus:border-app-yellow/70"
          }`}
          required
        />
        {errors && errors.months_count && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {errors.months_count}
          </span>
        )}
      </label>

      {isPrivatePlan ? (
        <div className="rounded-xl border border-yellow-400/25 bg-yellow-400/[0.04] p-4">
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال الكوتش *
              {selectedPlanObj && (
                <span className="ms-1 text-xs text-app-yellow">
                  ({formatMoney(selectedPlanObj?.coach_price)})
                </span>
              )}
              <input
                type="text"
                value={form.coach_receipt_number}
                onChange={(event) => updateField("coach_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors && errors.coach_receipt_number)}
                aria-describedby={
                  errors && errors.coach_receipt_number ? "coach-receipt-number-error" : undefined
                }
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors && errors.coach_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال الكوتش"
                maxLength={100}
                required
              />
              {errors && errors.coach_receipt_number && (
                <span
                  id="coach-receipt-number-error"
                  className="mt-1.5 block text-xs text-app-red"
                  role="alert"
                >
                  {errors.coach_receipt_number}
                </span>
              )}
            </label>

            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال النادي *
              {selectedPlanObj && (
                <span className="ms-1 text-xs text-app-yellow">
                  ({formatMoney(selectedPlanObj?.branch_price)})
                </span>
              )}
              <input
                type="text"
                value={form.branch_receipt_number}
                onChange={(event) => updateField("branch_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors && errors.branch_receipt_number)}
                aria-describedby={
                  errors && errors.branch_receipt_number ? "branch-receipt-number-error" : undefined
                }
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors && errors.branch_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال النادي"
                maxLength={100}
                required
              />
              {errors && errors.branch_receipt_number && (
                <span
                  id="branch-receipt-number-error"
                  className="mt-1.5 block text-xs text-app-red"
                  role="alert"
                >
                  {errors.branch_receipt_number}
                </span>
              )}
            </label>
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          رقم الإيصال *
          <input
            type="text"
            value={form.receipt_number}
            onChange={(event) => updateField("receipt_number", event.target.value)}
            aria-invalid={Boolean(errors && errors.receipt_number)}
            aria-describedby={errors && errors.receipt_number ? "receipt-number-error" : undefined}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
              errors && errors.receipt_number
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            placeholder="أدخل رقم الإيصال"
            maxLength={100}
            required
          />
          {errors && errors.receipt_number && (
            <span
              id="receipt-number-error"
              className="mt-1.5 block text-xs text-app-red"
              role="alert"
            >
              {errors.receipt_number}
            </span>
          )}
        </label>
      )}

      <div className="grid grid-cols-2 gap-3">
        <div>
          <DatePickerSmart
            label="تاريخ بداية الاشتراك *"
            value={form.start_date}
            onChange={(val) => updateField("start_date", val)}
            compact={false}
            disabled={isDailyEntryPlan}
            allowClear={!isDailyEntryPlan}
            error={errors && errors.start_date}
          />
        </div>

        <div>
          <DatePickerSmart
            label="تاريخ نهاية الاشتراك *"
            value={form.end_date}
            onChange={(val) => updateField("end_date", val)}
            compact={false}
            disabled={isDailyEntryPlan}
            allowClear={!isDailyEntryPlan}
            error={errors && errors.end_date}
          />
        </div>
      </div>

      {isDailyEntryPlan && (
        <p className="text-right text-xs text-app-muted-light">
          خطة دخولية ليوم واحد؛ تم ضبط تاريخ البداية والنهاية تلقائياً على تاريخ اليوم.
        </p>
      )}

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-center text-xs text-app-red">
          {errorMessage}
        </p>
      )}

      <div className={`${showFooterActions ? "flex" : "entry-form-actions-hidden"} gap-3 pt-2`}>
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          {cancelLabel || "إلغاء"}
        </Button>
        <Button
          type="submit"
          className="h-11 flex-1"
          loading={isLoading && submitAction === "normal"}
          onClick={() => setSubmitAction("normal")}
        >
          {submitLabel || "إنشاء الاشتراك"}
        </Button>
        {showAddAnother && (
          <Button
            type="submit"
            tone="outline"
            className="h-11 flex-1 border-app-yellow text-app-yellow hover:bg-app-yellow/10"
            loading={isLoading && submitAction === "addAnother"}
            onClick={() => setSubmitAction("addAnother")}
          >
            حفظ وإضافة آخر
          </Button>
        )}
      </div>
    </form>
  );
}

/**
 * Edits the fields supported by PUT /player-subscriptions/{id}.
 */
export function SubscriptionEditForm({
  subscription,
  members = [],
  plans = [],
  activityTypes = [],
  selectedActivityTypeId = "",
  onActivityTypeChange,
  isPlansLoading = false,
  plansErrorMessage = "",
  isActivityTypesLoading = false,
  activityTypesErrorMessage = "",
  onSubmit,
  onCancel,
  isLoading = false,
  errorMessage = "",
  canEditPaidAmount = false,
}) {
  const initialReceiptNumbers = getSubscriptionReceiptNumbers(subscription);
  const [form, setForm] = useState(() => ({
    member_id: String(subscription?.member_id || subscription?.member?.id || ""),
    plan_id: String(subscription?.plan_id || subscription?.plan?.id || ""),
    offer_id: String(subscription?.offer_id || subscription?.offer?.id || ""),
    months_count: String(subscription?.months_count || 1),
    start_date: String(subscription?.start_date || "").split("T")[0],
    end_date: String(subscription?.end_date || "").split("T")[0],
    status: subscription?.status || "active",
    paid_amount: String(subscription?.paid_amount ?? 0),
    receipt_number: String(initialReceiptNumbers.receiptNumber ?? ""),
    coach_receipt_number: String(initialReceiptNumbers.coachReceiptNumber ?? ""),
    branch_receipt_number: String(initialReceiptNumbers.branchReceiptNumber ?? ""),
    notes: subscription?.notes || "",
    reason: "",
  }));
  const [errors, setErrors] = useState({});
  const selectedPlan = plans.find((plan) => String(plan.id) === String(form.plan_id));
  const resolvedPlan = selectedPlan || subscription?.plan;
  const selectedActivityType = activityTypes.find(
    (activityType) => String(activityType.id) === String(selectedActivityTypeId),
  );
  const isDailyEntryPlan = isDailyEntrySubscriptionPlan(resolvedPlan);
  const originalPlanId = subscription?.plan_id || subscription?.plan?.id;
  const isOriginalPlanSelected = String(form.plan_id) === String(originalPlanId);
  const isPrivatePlan = isPrivateSubscriptionPlan(resolvedPlan, selectedActivityType);
  const splitPaymentAmounts = getSubscriptionSplitPaymentAmounts(
    subscription,
    resolvedPlan,
    form.paid_amount,
    isOriginalPlanSelected,
  );

  function updateField(field, value) {
    setForm((current) => {
      const nextState = { ...current, [field]: value };
      if (!isDailyEntryPlan && (field === "start_date" || field === "months_count")) {
        const startDate = field === "start_date" ? value : current.start_date;
        const months = field === "months_count" ? value : current.months_count;
        if (startDate) nextState.end_date = getSubscriptionEndDate(startDate, months);
      }
      return nextState;
    });
    if (errors[field]) setErrors((current) => ({ ...current, [field]: null }));
  }

  function handlePlanChange(planId) {
    const nextPlan = plans.find((plan) => String(plan.id) === String(planId));
    const nextIsDailyEntry = isDailyEntrySubscriptionPlan(nextPlan);
    const today = nextIsDailyEntry ? getLocalDateValue() : "";

    setForm((current) => {
      const startDate = nextIsDailyEntry ? today : current.start_date;

      return {
        ...current,
        plan_id: planId,
        paid_amount: nextPlan ? String(nextPlan.base_price || "0") : current.paid_amount,
        receipt_number: "",
        coach_receipt_number: "",
        branch_receipt_number: "",
        start_date: startDate,
        end_date: nextIsDailyEntry
          ? today
          : getSubscriptionEndDate(startDate, current.months_count),
      };
    });
    setErrors((current) => ({
      ...current,
      plan_id: null,
      paid_amount: null,
      receipt_number: null,
      coach_receipt_number: null,
      branch_receipt_number: null,
      start_date: null,
      end_date: null,
    }));
  }

  function handleActivityTypeChange(activityTypeId) {
    setForm((current) => ({
      ...current,
      plan_id: "",
      receipt_number: "",
      coach_receipt_number: "",
      branch_receipt_number: "",
      start_date: isDailyEntryPlan ? "" : current.start_date,
      end_date: isDailyEntryPlan ? "" : current.end_date,
    }));
    setErrors((current) => ({ ...current, plan_id: null }));
    onActivityTypeChange?.(activityTypeId);
  }

  function handleSubmit(event) {
    event.preventDefault();
    const validationData = {
      member_id: form.member_id,
      plan_id: form.plan_id,
      offer_id: form.offer_id ? form.offer_id : null,
      months_count: form.months_count,
      start_date: form.start_date,
      end_date: form.end_date,
      status: form.status,
      paid_amount: form.paid_amount,
      payment_method: "cash",
      ...(isPrivatePlan
        ? {
            coach_receipt_number: form.coach_receipt_number,
            branch_receipt_number: form.branch_receipt_number,
            coach_paid_amount: splitPaymentAmounts.coachPaidAmount,
            branch_paid_amount: splitPaymentAmounts.branchPaidAmount,
          }
        : { receipt_number: form.receipt_number }),
      notes: form.notes.trim(),
      reason: form.reason,
    };
    const result = subscriptionEditSchema.safeParse(validationData);

    if (!result.success) {
      const formattedErrors = {};
      result.error.issues.forEach((issue) => {
        const key = issue.path.join("_");
        if (!formattedErrors[key]) formattedErrors[key] = issue.message;
      });
      setErrors(formattedErrors);
      return;
    }

    setErrors({});
    onSubmit(result.data);
  }

  return (
    <form noValidate onSubmit={handleSubmit} className="space-y-4" dir="rtl">
      <label className="block text-right text-sm text-app-muted-light">
        اللاعب العضو *
        <Dropdown
          searchable
          searchPlaceholder="ابحث عن اللاعب بالاسم..."
          className="mt-2 text-white"
          buttonClassName="h-11 bg-app-card-soft"
          value={form.member_id}
          onChange={(value) => updateField("member_id", value)}
          options={members.map((member) => ({
            value: String(member.id),
            label:
              member.person?.full_name ||
              `${member.first_name || ""} ${member.last_name || ""}`.trim() ||
              "عضو بدون اسم",
          }))}
          error={errors.member_id}
        />
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        نوع النشاط
        <Dropdown
          className="mt-2 text-white"
          buttonClassName="h-11 bg-app-card-soft"
          value={selectedActivityTypeId}
          onChange={handleActivityTypeChange}
          options={[
            { value: "", label: "الكل" },
            ...activityTypes.map((activityType) => ({
              value: String(activityType.id),
              label: formatLocalizedName(activityType.name) || `نوع النشاط #${activityType.id}`,
            })),
          ]}
          placeholder={isActivityTypesLoading ? "جاري تحميل أنواع الأنشطة..." : "اختر نوع النشاط"}
          disabled={isActivityTypesLoading}
        />
        {activityTypesErrorMessage && (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {activityTypesErrorMessage}
          </span>
        )}
      </label>

      <label className="block text-right text-sm text-app-muted-light">
        خطة الاشتراك *
        {isPlansLoading ? (
          <div
            className="mt-2 flex h-11 items-center justify-center gap-2 rounded-xl border border-app-line bg-app-card-soft text-xs text-app-muted-light"
            role="status"
          >
            <span className="size-4 animate-spin rounded-full border-2 border-app-muted border-t-app-yellow" />
            جاري تحميل باقات الاشتراك...
          </div>
        ) : (
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.plan_id}
            onChange={handlePlanChange}
            options={plans.map((plan) => ({
              value: String(plan.id),
              label: formatLocalizedName(plan.name),
            }))}
            placeholder="اختر الخطة"
            disabled={plans.length === 0 || Boolean(plansErrorMessage)}
            error={errors.plan_id}
          />
        )}
        {plansErrorMessage ? (
          <span className="mt-1.5 block text-xs text-app-red" role="alert">
            {plansErrorMessage}
          </span>
        ) : (
          !isPlansLoading &&
          plans.length === 0 && (
            <span className="mt-1.5 block text-xs text-app-muted-light" role="status">
              {selectedActivityTypeId
                ? "لا توجد باقات اشتراك متاحة لنوع النشاط المحدد"
                : "لا توجد باقات اشتراك متاحة حالياً"}
            </span>
          )
        )}
      </label>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          رقم العرض (اختياري)
          <input
            type="number"
            min="1"
            value={form.offer_id}
            onChange={(event) => updateField("offer_id", event.target.value)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${errors.offer_id ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.offer_id && (
            <span className="mt-1 block text-xs text-app-red">{errors.offer_id}</span>
          )}
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          عدد الأشهر *
          <input
            type="number"
            min="1"
            value={form.months_count}
            onChange={(event) => updateField("months_count", event.target.value)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${errors.months_count ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.months_count && (
            <span className="mt-1 block text-xs text-app-red">{errors.months_count}</span>
          )}
        </label>
      </div>

      {isPrivatePlan ? (
        <div className="rounded-xl border border-yellow-400/25 bg-yellow-400/[0.04] p-4">
          <div className="grid gap-3 sm:grid-cols-2">
            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال الكوتش *
              {resolvedPlan && (
                <span className="ms-1 text-xs text-app-yellow">
                  ({formatMoney(resolvedPlan?.coach_price)})
                </span>
              )}
              <input
                type="text"
                value={form.coach_receipt_number}
                onChange={(event) => updateField("coach_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors.coach_receipt_number)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors.coach_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال الكوتش"
                maxLength={100}
                required
              />
              {errors.coach_receipt_number && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.coach_receipt_number}
                </span>
              )}
            </label>

            <label className="block text-right text-sm text-app-muted-light">
              رقم إيصال النادي *
              {resolvedPlan && (
                <span className="ms-1 text-xs text-app-yellow">
                  ({formatMoney(resolvedPlan?.branch_price)})
                </span>
              )}
              <input
                type="text"
                value={form.branch_receipt_number}
                onChange={(event) => updateField("branch_receipt_number", event.target.value)}
                aria-invalid={Boolean(errors.branch_receipt_number)}
                className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
                  errors.branch_receipt_number
                    ? "border border-app-red focus:border-app-red"
                    : "focus:border-app-yellow/70"
                }`}
                placeholder="أدخل رقم إيصال النادي"
                maxLength={100}
                required
              />
              {errors.branch_receipt_number && (
                <span className="mt-1.5 block text-xs text-app-red" role="alert">
                  {errors.branch_receipt_number}
                </span>
              )}
            </label>
          </div>
        </div>
      ) : (
        <label className="block text-right text-sm text-app-muted-light">
          رقم الإيصال *
          <input
            type="text"
            value={form.receipt_number}
            onChange={(event) => updateField("receipt_number", event.target.value)}
            aria-invalid={Boolean(errors.receipt_number)}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none ${
              errors.receipt_number
                ? "border border-app-red focus:border-app-red"
                : "focus:border-app-yellow/70"
            }`}
            placeholder="أدخل رقم الإيصال"
            maxLength={100}
            required
          />
          {errors.receipt_number && (
            <span className="mt-1.5 block text-xs text-app-red" role="alert">
              {errors.receipt_number}
            </span>
          )}
        </label>
      )}

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <DatePickerSmart
          label="تاريخ البداية *"
          value={form.start_date}
          onChange={(value) => updateField("start_date", value)}
          disabled={isDailyEntryPlan}
          allowClear={!isDailyEntryPlan}
          error={errors.start_date}
        />
        <DatePickerSmart
          label="تاريخ النهاية *"
          value={form.end_date}
          onChange={(value) => updateField("end_date", value)}
          disabled={isDailyEntryPlan}
          allowClear={!isDailyEntryPlan}
          error={errors.end_date}
        />
      </div>

      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <label className="block text-right text-sm text-app-muted-light">
          الحالة *
          <Dropdown
            className="mt-2 text-white"
            buttonClassName="h-11 bg-app-card-soft"
            value={form.status}
            onChange={(value) => updateField("status", value)}
            options={SUBSCRIPTION_STATUS_OPTIONS.filter((option) => option.value !== "all")}
            error={errors.status}
          />
        </label>

        <label className="block text-right text-sm text-app-muted-light">
          المبلغ المدفوع ({CURRENCY_SYMBOL}) *
          <input
            type="number"
            min="0"
            step="0.01"
            value={form.paid_amount}
            onChange={(event) => updateField("paid_amount", event.target.value)}
            disabled={!canEditPaidAmount}
            title={!canEditPaidAmount ? "تعديل المبلغ متاح للأدمن فقط" : undefined}
            className={`app-input mt-2 h-11 w-full bg-app-card-soft px-3 text-right text-white outline-none disabled:cursor-not-allowed disabled:opacity-60 ${errors.paid_amount ? "border-app-red" : "focus:border-app-yellow/70"}`}
          />
          {errors.paid_amount && (
            <span className="mt-1 block text-xs text-app-red">{errors.paid_amount}</span>
          )}
          {!canEditPaidAmount && (
            <span className="mt-1.5 block text-xs text-app-muted-light">
              تعديل المبلغ متاح للأدمن فقط.
            </span>
          )}
        </label>
      </div>

      <label className="block text-right text-sm text-app-muted-light">
        الملاحظات
        <textarea
          rows={4}
          value={form.notes}
          onChange={(event) => updateField("notes", event.target.value)}
          className={`app-input mt-2 min-h-24 w-full resize-y bg-app-card-soft px-3 py-3 text-right text-white outline-none ${errors.notes ? "border-app-red" : "focus:border-app-yellow/70"}`}
        />
        {errors.notes && <span className="mt-1 block text-xs text-app-red">{errors.notes}</span>}
      </label>

      <ModificationReasonField
        value={form.reason}
        onChange={(value) => updateField("reason", value)}
        error={errors.reason}
      />

      {errorMessage && (
        <p className="rounded-xl border border-app-red/30 bg-app-red/10 p-3 text-sm text-app-red">
          {errorMessage}
        </p>
      )}

      <div className="flex gap-3 border-t border-app-line pt-4">
        <Button type="button" tone="outline" className="h-11 flex-1" onClick={onCancel}>
          إلغاء
        </Button>
        <Button type="submit" className="h-11 flex-1 text-black" loading={isLoading}>
          حفظ التعديلات
        </Button>
      </div>
    </form>
  );
}
