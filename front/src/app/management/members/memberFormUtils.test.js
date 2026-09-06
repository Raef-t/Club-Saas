import { describe, expect, it } from "vitest";
import { getMemberEditInitialValues } from "./memberFormUtils";

describe("member form utilities", () => {
  it("maps the member details API response to edit form values", () => {
    expect(
      getMemberEditInitialValues({
        id: 66,
        branch_id: 5,
        person: {
          full_name: "هديل لبابيدي",
          gender: "female",
          age: null,
          dob: null,
          contacts: [
            {
              id: 266,
              name: "Personal",
              country_code: "+963",
              phone_number: "943578427",
              relation: "self",
            },
          ],
        },
      }),
    ).toEqual({
      first_name: "هديل",
      last_name: "لبابيدي",
      mobile_country_code: "+963",
      mobile: "943578427",
      gender: "female",
      dob: "",
      age: "",
      branch_id: "5",
      emergency_name: "",
      emergency_relation: "Father",
      emergency_country_code: "+963",
      emergency_phone: "",
      reason: "",
    });
  });

  it("keeps personal and emergency contacts in their matching fields", () => {
    const values = getMemberEditInitialValues({
      branch_id: 2,
      person: {
        full_name: "أحمد سمان",
        dob: "2000-04-03T00:00:00.000000Z",
        contacts: [
          { name: "Personal", relation: "self", country_code: "+963", phone_number: "911111111" },
          {
            name: "والد اللاعب",
            relation: "Father",
            country_code: "+963",
            phone_number: "922222222",
          },
        ],
      },
    });

    expect(values).toMatchObject({
      mobile: "911111111",
      dob: "2000-04-03",
      emergency_name: "والد اللاعب",
      emergency_relation: "Father",
      emergency_phone: "922222222",
    });
  });
});
