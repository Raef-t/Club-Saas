/** Displays the member name with only their phone number underneath. */
export default function SubscriptionMemberIdentity({ subscription }) {
  const person = subscription?.member?.person || {};

  return (
    <div className="min-w-0 text-center">
      <p className="truncate text-sm font-medium text-app-text">{person.full_name || "-"}</p>
      {person.phone && (
        <p className="mt-1 truncate text-[11px] text-app-muted-light" dir="ltr">
          {person.phone}
        </p>
      )}
    </div>
  );
}
