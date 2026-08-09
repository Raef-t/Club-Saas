function isRecord(value) {
  return value && typeof value === "object" && !Array.isArray(value);
}

function collectResponseRecords(response, entityKeys) {
  const records = [];
  const queue = [response?.data, response].filter(isRecord);
  const visited = new Set();
  const preferredKeys = [
    ...entityKeys,
    "credentials",
    "login_credentials",
    "account",
    "user",
    "data",
  ];

  while (queue.length > 0 && records.length < 24) {
    const current = queue.shift();
    if (!isRecord(current) || visited.has(current)) continue;

    visited.add(current);
    records.push(current);

    preferredKeys.forEach((key) => {
      if (isRecord(current[key])) queue.push(current[key]);
    });
  }

  return records;
}

function findValue(records, keys) {
  for (const key of keys) {
    for (const record of records) {
      const value = record?.[key];
      if (value !== undefined && value !== null && value !== "") return value;
    }
  }

  return "";
}

/**
 * Normalizes generated login details across the staff, coach, and member
 * creation response envelopes used by the backend.
 */
export function extractCreatedAccount(response, { entityKeys = [] } = {}) {
  const records = collectResponseRecords(response, entityKeys);

  return {
    id: findValue(records, ["id"]),
    username: String(findValue(records, ["generated_username", "username"])),
    password: String(findValue(records, ["generated_password", "temporary_password", "password"])),
  };
}
