export function getOffersCollection(data) {
  if (Array.isArray(data?.data?.data)) return data.data.data;
  if (Array.isArray(data?.data)) return data.data;
  if (Array.isArray(data)) return data;
  return [];
}

export function filterOffers(offers, { searchTerm = "", statusFilter = "all" } = {}) {
  const query = searchTerm.trim().toLocaleLowerCase("ar");

  return offers.filter((offer) => {
    if (query) {
      const searchableValues = [
        offer.name,
        offer.description,
        ...(offer.plans || []).map((plan) =>
          typeof plan.name === "object" ? plan.name?.ar || plan.name?.en : plan.name,
        ),
      ];

      if (
        !searchableValues.some((value) =>
          String(value || "")
            .toLocaleLowerCase("ar")
            .includes(query),
        )
      ) {
        return false;
      }
    }

    if (statusFilter === "available" && !offer.is_available) return false;
    if (statusFilter === "inactive" && offer.is_active && offer.is_available) return false;

    return true;
  });
}

export function getPlanCapacity(plan) {
  const isUnlimited = Boolean(
    plan.is_unlimited_subscribers ||
    plan.max_subscribers === 0 ||
    plan.max_subscribers === null ||
    plan.max_subscribers === undefined,
  );

  if (isUnlimited) {
    return { isUnlimited: true, isFull: false, availableSeats: Infinity, label: "غير محدود" };
  }

  const availableSeats =
    plan.available_slots !== undefined && plan.available_slots !== null
      ? Math.max(0, Number(plan.available_slots))
      : Math.max(0, (Number(plan.max_subscribers) || 0) - (Number(plan.current_subscribers) || 0));

  return {
    isUnlimited: false,
    isFull: availableSeats <= 0,
    availableSeats,
    label: availableSeats <= 0 ? "مكتمل" : `${availableSeats} مقعد`,
  };
}

export function getOfferStatus(offer) {
  if (!offer.is_active) return { label: "معطل", tone: "danger" };

  if (offer.end_date && new Date(offer.end_date) < new Date().setHours(0, 0, 0, 0)) {
    return { label: "منتهي الصلاحية", tone: "danger" };
  }

  if (offer.is_available) {
    const slots =
      offer.offer_type !== "single_choice" &&
      offer.available_slots !== null &&
      offer.available_slots !== undefined
        ? ` (${offer.available_slots} مقعد)`
        : "";

    return { label: `متاح${slots}`, tone: "success" };
  }

  return { label: "مكتمل السعة", tone: "warning" };
}
