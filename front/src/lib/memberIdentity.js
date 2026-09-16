function normalizeAccountName(value) {
  if (value == null) return "";
  return String(value).trim();
}

function getIdentityRecords(source) {
  if (!source || typeof source !== "object") return [];

  return [source, source.member, source.player, source.user, source.account].filter(Boolean);
}

/**
 * Resolves the public account name for a member without exposing the internal
 * membership number. A player-selected username always wins over the
 * system-generated username, regardless of the API response shape.
 */
export function getMemberAccountName(...sources) {
  const records = sources.flatMap(getIdentityRecords);
  const customName = records
    .map((record) => normalizeAccountName(record.custom_username ?? record.customUsername))
    .find(Boolean);

  if (customName) return customName;

  return (
    records
      .map((record) =>
        normalizeAccountName(
          record.generated_username ?? record.generatedUsername ?? record.username,
        ),
      )
      .find(Boolean) || ""
  );
}

function getCreatorRecords(source) {
  if (!source || typeof source !== "object") return [];

  return [
    source.created_by,
    source.createdBy,
    source.registered_by,
    source.registeredBy,
    source.creator,
  ].filter(Boolean);
}

/**
 * Resolves the username of the employee who registered a member. List and
 * detail endpoints have used different creator shapes, so keep the display
 * logic in one place and retain the legacy name as a last-resort fallback.
 */
export function getMemberCreatorUsername(...sources) {
  const directUsername = sources
    .flatMap(getIdentityRecords)
    .map((record) =>
      normalizeAccountName(
        record.created_by_username ??
          record.createdByUsername ??
          record.registered_by_username ??
          record.registeredByUsername,
      ),
    )
    .find(Boolean);

  if (directUsername) return directUsername;

  const creators = sources.flatMap(getIdentityRecords).flatMap(getCreatorRecords);
  const creatorUsername = creators
    .map((creator) =>
      typeof creator === "string"
        ? normalizeAccountName(creator)
        : normalizeAccountName(
            creator?.custom_username ??
              creator?.customUsername ??
              creator?.username ??
              creator?.generated_username ??
              creator?.generatedUsername,
          ),
    )
    .find(Boolean);

  if (creatorUsername) return creatorUsername;

  return (
    creators
      .map((creator) =>
        typeof creator === "string"
          ? normalizeAccountName(creator)
          : normalizeAccountName(creator?.name ?? creator?.full_name ?? creator?.fullName),
      )
      .find(Boolean) || ""
  );
}
