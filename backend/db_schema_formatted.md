# Club SaaS Database Schema & Relationships

This document maps out the tables in the Club SaaS application, starting from the top-level **Clubs** and **Branches** down to their dependent entities. It describes the fields in each table and the foreign key relationships.

## Table: `clubs`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **logo_url** | `varchar(255)` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO | MUL | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `club_settings`

### Relationships (Foreign Keys):
- **`club_id`** points to **`clubs.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **club_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **theme_colors** | `longtext` | YES |  | *NULL* |  |
| **language** | `varchar(10)` | NO |  | `all` |  |
| **allowed_debt_limit** | `decimal(12,2)` | NO |  | `0.00` |  |
| **grace_period_days** | `int(11)` | NO |  | `0` |  |
| **allow_partial_payment** | `tinyint(1)` | NO |  | `1` |  |
| **enabled_features** | `longtext` | YES |  | *NULL* |  |
| **bg_image_url** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `branches`

### Relationships (Foreign Keys):
- **`club_id`** points to **`clubs.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **club_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **gender_restriction** | `varchar(20)` | NO | MUL | `mixed` |  |
| **type** | `varchar(50)` | YES |  | *NULL* |  |
| **address** | `text` | YES |  | *NULL* |  |
| **phone** | `varchar(20)` | YES |  | *NULL* |  |
| **country_code** | `varchar(5)` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO | MUL | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `branch_settings`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **working_hours_start** | `time` | YES |  | *NULL* |  |
| **working_hours_end** | `time` | YES |  | *NULL* |  |
| **default_club_commission_percentage** | `decimal(5,2)` | YES |  | *NULL* |  |
| **default_coach_commission_percentage** | `decimal(5,2)` | YES |  | *NULL* |  |
| **default_employee_salary** | `decimal(10,2)` | YES |  | *NULL* |  |
| **daily_entry_price** | `decimal(10,2)` | YES |  | `0.00` |  |
| **locker_price** | `decimal(10,2)` | NO |  | `30000.00` |  |
| **allow_freeze** | `tinyint(1)` | NO |  | `0` |  |
| **display_mixed_activities** | `tinyint(1)` | NO |  | `0` |  |
| **payroll_start_day** | `tinyint(4)` | YES |  | *NULL* |  |
| **payroll_end_day** | `tinyint(4)` | YES |  | *NULL* |  |
| **include_terminated_subscriptions** | `tinyint(1)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `branch_holidays`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | YES |  | *NULL* |  |
| **type** | `enum('weekly','specific_dates')` | NO |  | `weekly` |  |
| **day_of_week** | `varchar(255)` | YES |  | *NULL* |  |
| **start_date** | `date` | YES |  | *NULL* |  |
| **end_date** | `date` | YES |  | *NULL* |  |
| **reason** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `branch_shifts`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | YES |  | *NULL* |  |
| **facility_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **day_of_week** | `int(11)` | NO |  | *NULL* |  |
| **start_time** | `time` | NO |  | *NULL* |  |
| **end_time** | `time` | NO |  | *NULL* |  |
| **gender_allowed** | `varchar(20)` | NO |  | `mixed` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `facilities`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **capacity** | `int(11)` | YES |  | *NULL* |  |
| **gender_restriction** | `varchar(20)` | NO | MUL | `mixed` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `facility_working_hours`

### Relationships (Foreign Keys):
- **`facility_id`** points to **`facilities.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **facility_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **day_of_week** | `int(11)` | NO |  | *NULL* |  |
| **open_time** | `time` | NO |  | *NULL* |  |
| **close_time** | `time` | NO |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `lockers`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **locker_number** | `varchar(255)` | NO |  | *NULL* |  |
| **key_number** | `varchar(255)` | YES |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `available` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `people`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **full_name** | `varchar(255)` | NO | MUL | *NULL* |  |
| **gender** | `varchar(10)` | YES |  | *NULL* |  |
| **type** | `enum('player','coach','staff','admin')` | NO |  | `player` |  |
| **dob** | `date` | YES |  | *NULL* |  |
| **age** | `int(11)` | YES |  | *NULL* |  |
| **national_id** | `varchar(50)` | YES | MUL | *NULL* |  |
| **social_status** | `varchar(50)` | YES |  | *NULL* |  |
| **children_count** | `int(11)` | YES |  | *NULL* |  |
| **address** | `text` | YES |  | *NULL* |  |
| **photo_url** | `varchar(255)` | YES |  | *NULL* |  |
| **email** | `varchar(255)` | YES | MUL | *NULL* |  |
| **chronic_diseases** | `text` | YES |  | *NULL* |  |
| **how_did_you_hear** | `varchar(100)` | YES |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `users`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **email** | `varchar(255)` | NO | UNI | *NULL* |  |
| **email_verified_at** | `timestamp` | YES |  | *NULL* |  |
| **password** | `varchar(255)` | NO |  | *NULL* |  |
| **remember_token** | `varchar(100)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `coach_details`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | UNI | *NULL* |  |
| **bio** | `text` | YES |  | *NULL* |  |
| **experience_years** | `int(11)` | NO |  | `0` |  |
| **gym_type** | `varchar(20)` | YES |  | *NULL* |  |
| **work_types** | `longtext` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `coach_certifications`

### Relationships (Foreign Keys):
- **`coach_detail_id`** points to **`coach_details.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **coach_detail_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **issuer** | `varchar(255)` | YES |  | *NULL* |  |
| **issue_date** | `date` | YES |  | *NULL* |  |
| **expiry_date** | `date` | YES |  | *NULL* |  |
| **document_url** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff`

### Relationships (Foreign Keys):
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **role** | `enum('admin','receptionist','coach','cleaner','manager','staff')` | NO | MUL | `staff` |  |
| **start_date** | `date` | YES |  | *NULL* |  |
| **end_date** | `date` | YES |  | *NULL* |  |
| **work_status** | `varchar(50)` | NO |  | `active` |  |
| **is_active** | `tinyint(1)` | NO | MUL | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_branches`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `activity_types`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **is_session_based** | `tinyint(1)` | NO |  | `1` |  |
| **has_unlimited_subscribers** | `tinyint(1)` | NO |  | `0` |  |
| **has_shifts** | `tinyint(1)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `activities`

### Relationships (Foreign Keys):
- **`activity_type_id`** points to **`activity_types.id`**
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **name** | `varchar(150)` | NO |  | *NULL* |  |
| **description** | `text` | YES |  | *NULL* |  |
| **activity_type_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **is_private_equipment** | `tinyint(1)` | NO |  | `0` |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **session_price** | `decimal(10,2)` | NO |  | `0.00` |  |
| **exercises_count** | `int(11)` | NO |  | `0` |  |
| **estimated_calories** | `int(11)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `sport_session_templates`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **plan_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **facility_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **day_of_week** | `int(11)` | NO |  | *NULL* |  |
| **start_time** | `time` | NO |  | *NULL* |  |
| **end_time** | `time` | NO |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_activities`

### Relationships (Foreign Keys):
- **`activity_id`** points to **`activities.id`**
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **activity_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_commission_rules`

### Relationships (Foreign Keys):
- **`activity_id`** points to **`activities.id`**
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **activity_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **calculation_type** | `varchar(50)` | NO |  | *NULL* |  |
| **rate_value** | `decimal(8,2)` | NO |  | *NULL* |  |
| **min_players** | `int(11)` | YES |  | *NULL* |  |
| **max_players** | `int(11)` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `subscription_plans`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **subscription_number** | `varchar(255)` | YES | UNI | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **name** | `varchar(150)` | NO |  | *NULL* |  |
| **gender_restriction** | `varchar(50)` | NO |  | `mixed` |  |
| **session_count** | `int(11)` | YES |  | *NULL* |  |
| **sessions_per_week** | `int(11)` | YES |  | *NULL* |  |
| **base_price** | `decimal(12,2)` | NO |  | *NULL* |  |
| **status** | `varchar(255)` | NO |  | `active` |  |
| **max_subscribers** | `int(11)` | YES |  | *NULL* |  |
| **current_subscribers** | `int(11)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `plan_activities`

### Relationships (Foreign Keys):
- **`plan_id`** points to **`subscription_plans.id`**
- **`staff_activity_id`** points to **`staff_activities.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **plan_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **staff_activity_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `player_subscriptions`

### Relationships (Foreign Keys):
- **`member_id`** points to **`members.id`**
- **`offer_id`** points to **`offers.id`**
- **`plan_id`** points to **`subscription_plans.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **plan_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **months_count** | `int(10) unsigned` | NO |  | `1` |  |
| **total_amount** | `decimal(12,2)` | NO |  | `0.00` |  |
| **paid_amount** | `decimal(12,2)` | NO |  | `0.00` |  |
| **remaining_amount** | `decimal(12,2)` | NO |  | `0.00` |  |
| **branch_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **start_date** | `date` | NO | MUL | *NULL* |  |
| **end_date** | `date` | YES | MUL | *NULL* |  |
| **last_used_at** | `timestamp` | YES |  | *NULL* |  |
| **status** | `enum('active','finished','frozen','terminated')` | YES | MUL | `active` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **offer_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `player_subscription_items`

### Relationships (Foreign Keys):
- **`activity_id`** points to **`activities.id`**
- **`player_subscription_id`** points to **`player_subscriptions.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **player_subscription_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **activity_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **coach_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **sessions_allocated** | `int(11)` | YES |  | *NULL* |  |
| **sessions_consumed** | `int(11)` | NO |  | `0` |  |
| **is_unlimited** | `tinyint(1)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `subscription_freezes`

### Relationships (Foreign Keys):
- **`player_subscription_id`** points to **`player_subscriptions.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **player_subscription_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **freeze_start_date** | `date` | NO |  | *NULL* |  |
| **freeze_end_date** | `date` | YES |  | *NULL* |  |
| **actual_end_date** | `date` | YES |  | *NULL* |  |
| **reason** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `leads`

### Relationships (Foreign Keys):
- **`assigned_to_user_id`** points to **`authentication_users.id`**
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **full_name** | `varchar(255)` | NO |  | *NULL* |  |
| **phone** | `varchar(20)` | YES |  | *NULL* |  |
| **email** | `varchar(255)` | YES |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `new` |  |
| **source** | `varchar(100)` | YES |  | *NULL* |  |
| **assigned_to_user_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **pipeline_stage** | `varchar(50)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `lead_interactions`

### Relationships (Foreign Keys):
- **`lead_id`** points to **`leads.id`**
- **`user_id`** points to **`authentication_users.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **lead_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **user_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **interaction_type** | `varchar(50)` | NO |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **next_follow_up** | `datetime` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `invoices`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**
- **`member_id`** points to **`members.id`**
- **`offer_id`** points to **`offers.id`**
- **`player_subscription_id`** points to **`player_subscriptions.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **member_name** | `varchar(255)` | YES |  | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **player_subscription_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **total** | `decimal(12,2)` | NO |  | `0.00` |  |
| **status** | `varchar(50)` | NO |  | `unpaid` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **offer_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `payments`

### Relationships (Foreign Keys):
- **`invoice_id`** points to **`invoices.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **invoice_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **safe_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **amount** | `decimal(12,2)` | NO |  | `0.00` |  |
| **payment_method** | `varchar(50)` | NO |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `completed` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `wallets`

### Relationships (Foreign Keys):
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **balance** | `decimal(15,2)` | NO |  | `0.00` |  |
| **status** | `varchar(255)` | NO |  | `active` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `wallet_transactions`

### Relationships (Foreign Keys):
- **`wallet_id`** points to **`wallets.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **wallet_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **amount** | `decimal(15,2)` | NO |  | *NULL* |  |
| **type** | `varchar(255)` | NO |  | *NULL* |  |
| **reference_type** | `varchar(255)` | YES | MUL | *NULL* |  |
| **reference_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **description** | `text` | YES |  | *NULL* |  |
| **created_by** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_shifts`

### Relationships (Foreign Keys):
- **`branch_shift_id`** points to **`branch_shifts.id`**
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **branch_shift_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_working_hours`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **day_of_week** | `tinyint(4)` | NO |  | *NULL* |  |
| **start_time** | `time` | NO |  | *NULL* |  |
| **end_time** | `time` | NO |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_unavailabilities`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **start_datetime** | `datetime` | NO |  | *NULL* |  |
| **end_datetime** | `datetime` | NO |  | *NULL* |  |
| **reason** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_leaves`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **start_date** | `date` | NO |  | *NULL* |  |
| **end_date** | `date` | NO |  | *NULL* |  |
| **reason** | `varchar(255)` | YES |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `approved` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `payroll_runs`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **period_start** | `date` | NO |  | *NULL* |  |
| **period_end** | `date` | NO |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `draft` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `payslips`

### Relationships (Foreign Keys):
- **`payroll_run_id`** points to **`payroll_runs.id`**
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **payroll_run_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **staff_name** | `varchar(255)` | YES |  | *NULL* |  |
| **base_pay** | `decimal(12,2)` | NO |  | `0.00` |  |
| **commission_pay** | `decimal(12,2)` | NO |  | `0.00` |  |
| **net_pay** | `decimal(12,2)` | NO |  | `0.00` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_accounts`

### Relationships (Foreign Keys):
- **`parent_id`** points to **`acc_accounts.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **code** | `varchar(20)` | NO | UNI | *NULL* |  |
| **name** | `varchar(150)` | NO |  | *NULL* |  |
| **name_en** | `varchar(150)` | YES |  | *NULL* |  |
| **type** | `enum('asset','liability','equity','revenue','expense')` | NO | MUL | *NULL* |  |
| **currency** | `enum('USD','SYP','BOTH')` | NO |  | `BOTH` |  |
| **parent_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **allow_manual_entry** | `tinyint(1)` | NO |  | `1` |  |
| **description** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_branch_settings`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**
- **`default_safe_id`** points to **`acc_safes.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | UNI | *NULL* |  |
| **default_safe_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **cash_usd_account_code** | `varchar(50)` | YES |  | *NULL* |  |
| **cash_syp_account_code** | `varchar(50)` | YES |  | *NULL* |  |
| **revenue_account_code** | `varchar(50)` | YES |  | *NULL* |  |
| **expense_account_code** | `varchar(50)` | YES |  | *NULL* |  |
| **supported_currencies** | `longtext` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_counterparties`

### Relationships (Foreign Keys):
- **`ar_account_id`** points to **`acc_accounts.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(150)` | NO |  | *NULL* |  |
| **type** | `enum('customer','vendor','employee','other')` | NO |  | `customer` |  |
| **ar_account_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **phone** | `varchar(30)` | YES |  | *NULL* |  |
| **country_code** | `varchar(5)` | YES |  | *NULL* |  |
| **email** | `varchar(150)` | YES |  | *NULL* |  |
| **reference_type** | `varchar(100)` | YES | MUL | *NULL* |  |
| **reference_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_journal_entries`

### Relationships (Foreign Keys):
- **`account_id`** points to **`acc_accounts.id`**
- **`journal_id`** points to **`acc_journals.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **journal_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **account_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **debit_usd** | `decimal(18,4)` | NO |  | `0.0000` |  |
| **credit_usd** | `decimal(18,4)` | NO |  | `0.0000` |  |
| **debit_syp** | `decimal(20,2)` | NO |  | `0.00` |  |
| **credit_syp** | `decimal(20,2)` | NO |  | `0.00` |  |
| **memo** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_journals`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**
- **`counterparty_id`** points to **`acc_counterparties.id`**
- **`period_id`** points to **`acc_periods.id`**
- **`safe_id`** points to **`acc_safes.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **reference_number** | `varchar(50)` | NO | UNI | *NULL* |  |
| **type** | `enum('JV','RV','PV','SI','PI')` | NO | MUL | `JV` |  |
| **period_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **date** | `date` | NO | MUL | *NULL* |  |
| **description** | `text` | NO |  | *NULL* |  |
| **counterparty_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **safe_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **exchange_rate** | `decimal(12,4)` | YES |  | *NULL* |  |
| **status** | `enum('draft','posted','reversed')` | NO |  | `draft` |  |
| **posted_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **posted_at** | `timestamp` | YES |  | *NULL* |  |
| **reversed_journal_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **source_type** | `varchar(100)` | YES | MUL | *NULL* |  |
| **source_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_partners`

### Relationships (Foreign Keys):
- **`capital_account_id`** points to **`acc_accounts.id`**
- **`drawings_account_id`** points to **`acc_accounts.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(100)` | NO |  | *NULL* |  |
| **capital_account_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **drawings_account_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **profit_share_pct** | `decimal(5,2)` | NO |  | `0.00` |  |
| **joined_at** | `date` | NO |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_periods`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(100)` | NO |  | *NULL* |  |
| **start_date** | `date` | NO |  | *NULL* |  |
| **end_date** | `date` | NO |  | *NULL* |  |
| **status** | `enum('open','closed','locked')` | NO |  | `open` |  |
| **closed_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **closed_at** | `timestamp` | YES |  | *NULL* |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_reconciliations`

### Relationships (Foreign Keys):
- **`period_id`** points to **`acc_periods.id`**
- **`safe_id`** points to **`acc_safes.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **safe_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **period_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **system_balance_usd** | `decimal(18,4)` | NO |  | `0.0000` |  |
| **physical_balance_usd** | `decimal(18,4)` | NO |  | `0.0000` |  |
| **difference_usd** | `decimal(18,4)` | YES |  | *NULL* | STORED GENERATED |
| **system_balance_syp** | `decimal(20,2)` | NO |  | `0.00` |  |
| **physical_balance_syp** | `decimal(20,2)` | NO |  | `0.00` |  |
| **difference_syp** | `decimal(20,2)` | YES |  | *NULL* | STORED GENERATED |
| **reconciled_by** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **reconciled_at** | `timestamp` | NO |  | `current_timestamp()` | on update current_timestamp() |
| **notes** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `acc_safes`

### Relationships (Foreign Keys):
- **`account_id`** points to **`acc_accounts.id`**
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(100)` | NO |  | *NULL* |  |
| **account_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **currency** | `enum('USD','SYP')` | NO |  | `USD` |  |
| **responsible_user_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **notes** | `text` | YES |  | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `attendance_consumptions`

### Relationships (Foreign Keys):
- **`attendance_id`** points to **`attendances.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **attendance_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **subscription_plan_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **player_subscription_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `attendances`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **attendable_type** | `varchar(255)` | NO |  | *NULL* |  |
| **attendable_id** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **recorded_by_staff_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **check_in_at** | `datetime` | NO |  | *NULL* |  |
| **check_out_at** | `datetime` | YES |  | *NULL* |  |
| **duration_minutes** | `int(11)` | YES |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `checked_in` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `authentication_users`

### Relationships (Foreign Keys):
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **username** | `varchar(255)` | NO | UNI | *NULL* |  |
| **password** | `varchar(255)` | NO |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **role** | `varchar(255)` | YES |  | *NULL* |  |
| **last_login** | `timestamp` | YES |  | *NULL* |  |
| **remember_token** | `varchar(100)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `command_executions`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **command_signature** | `varchar(255)` | NO | MUL | *NULL* |  |
| **period** | `varchar(255)` | NO | MUL | *NULL* |  |
| **executed_at** | `timestamp` | NO |  | `current_timestamp()` | on update current_timestamp() |
| **status** | `varchar(255)` | NO |  | `success` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `domain_events`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **event_type** | `varchar(100)` | NO |  | *NULL* |  |
| **aggregate_type** | `varchar(100)` | NO |  | *NULL* |  |
| **aggregate_id** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **payload** | `longtext` | YES |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `pending` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `formula_variables`

### Relationships (Foreign Keys):
- **`formula_id`** points to **`formulas.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **formula_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **variable_name** | `varchar(255)` | NO |  | *NULL* |  |
| **source_type** | `enum('input','measurement','computed')` | NO |  | *NULL* |  |
| **db_column** | `varchar(255)` | YES |  | *NULL* |  |
| **computed_formula_key** | `varchar(255)` | YES |  | *NULL* |  |
| **is_required** | `tinyint(1)` | NO |  | `1` |  |
| **default_value** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `formulas`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **key** | `varchar(255)` | NO | UNI | *NULL* |  |
| **expression** | `text` | NO |  | *NULL* |  |
| **description** | `text` | YES |  | *NULL* |  |
| **category** | `enum('measurement','general')` | NO |  | `measurement` |  |
| **return_type** | `enum('float','int','bool','string')` | NO |  | `float` |  |
| **unit** | `varchar(255)` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **is_system** | `tinyint(1)` | NO |  | `0` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `gate_devices`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **club_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **mac_address** | `varchar(255)` | YES |  | *NULL* |  |
| **api_token** | `varchar(80)` | YES | UNI | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `locker_reservations`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **locker_id** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **member_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **staff_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **invoice_id** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **start_date** | `date` | NO |  | *NULL* |  |
| **end_date** | `date` | YES |  | *NULL* |  |
| **price** | `decimal(10,2)` | NO |  | `0.00` |  |
| **status** | `varchar(255)` | NO |  | `active` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `member_evaluations`

### Relationships (Foreign Keys):
- **`member_id`** points to **`members.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **evaluatable_type** | `varchar(255)` | NO | MUL | *NULL* |  |
| **evaluatable_id** | `bigint(20) unsigned` | NO |  | *NULL* |  |
| **rating** | `tinyint(3) unsigned` | NO |  | *NULL* |  |
| **comment** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `member_health_profiles`

### Relationships (Foreign Keys):
- **`member_id`** points to **`members.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **allergies** | `text` | YES |  | *NULL* |  |
| **organic_diseases** | `text` | YES |  | *NULL* |  |
| **physical_injuries** | `text` | YES |  | *NULL* |  |
| **medications** | `text` | YES |  | *NULL* |  |
| **blood_type** | `varchar(5)` | YES |  | *NULL* |  |
| **emergency_contact_name** | `varchar(100)` | YES |  | *NULL* |  |
| **emergency_contact_phone** | `varchar(20)` | YES |  | *NULL* |  |
| **emergency_contact_country_code** | `varchar(5)` | YES |  | *NULL* |  |
| **sport_goal** | `varchar(100)` | YES |  | *NULL* |  |
| **fitness_level** | `varchar(50)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `member_measurements`

### Relationships (Foreign Keys):
- **`member_id`** points to **`members.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **measurement_date** | `date` | NO | MUL | *NULL* |  |
| **weight** | `decimal(5,2)` | YES |  | *NULL* |  |
| **height** | `decimal(5,2)` | YES |  | *NULL* |  |
| **body_fat_percentage** | `decimal(5,2)` | YES |  | *NULL* |  |
| **muscle_mass** | `decimal(5,2)` | YES |  | *NULL* |  |
| **waist_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **neck_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **chest_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **thigh_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **arm_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **activity_level** | `varchar(50)` | YES |  | *NULL* |  |
| **bmi** | `decimal(5,2)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **shoulder_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **right_bicep** | `decimal(5,2)` | YES |  | *NULL* |  |
| **left_bicep** | `decimal(5,2)` | YES |  | *NULL* |  |
| **hip_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **right_thigh_mid** | `decimal(5,2)` | YES |  | *NULL* |  |
| **left_thigh** | `decimal(5,2)` | YES |  | *NULL* |  |
| **right_calf** | `decimal(5,2)` | YES |  | *NULL* |  |
| **left_calf** | `decimal(5,2)` | YES |  | *NULL* |  |
| **fat_free_mass_percentage** | `decimal(5,2)` | YES |  | *NULL* |  |
| **body_water_percentage** | `decimal(5,2)` | YES |  | *NULL* |  |
| **resting_metabolic_rate** | `decimal(6,2)` | YES |  | *NULL* |  |
| **total_daily_energy_expenditure** | `decimal(6,2)` | YES |  | *NULL* |  |
| **physical_activity_level** | `enum('sedentary','light','medium','high','extreme')` | YES |  | *NULL* |  |
| **buttocks_circumference** | `decimal(5,2)` | YES |  | *NULL* |  |
| **above_right_knee** | `decimal(5,2)` | YES |  | *NULL* |  |
| **above_left_knee** | `decimal(5,2)` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `members`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **member_number** | `varchar(255)` | NO | UNI | *NULL* |  |
| **membership_status** | `varchar(255)` | NO | MUL | `active` |  |
| **join_date** | `date` | YES |  | *NULL* |  |
| **how_heard_about_us** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `notification_attachments`

### Relationships (Foreign Keys):
- **`notification_id`** points to **`notifications.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **notification_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **file_path** | `varchar(255)` | NO |  | *NULL* |  |
| **file_name** | `varchar(255)` | NO |  | *NULL* |  |
| **mime_type** | `varchar(255)` | YES |  | *NULL* |  |
| **size** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `notification_recipients`

### Relationships (Foreign Keys):
- **`notification_id`** points to **`notifications.id`**
- **`user_id`** points to **`authentication_users.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **notification_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **user_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **delivered_at** | `timestamp` | YES |  | *NULL* |  |
| **read_at** | `timestamp` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `notification_templates`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **system_key** | `varchar(255)` | NO | UNI | *NULL* |  |
| **subject** | `varchar(255)` | YES |  | *NULL* |  |
| **body** | `text` | NO |  | *NULL* |  |
| **variables** | `longtext` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `notifications`

### Relationships (Foreign Keys):
- **`sender_id`** points to **`authentication_users.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **title** | `varchar(255)` | NO |  | *NULL* |  |
| **body** | `text` | NO |  | *NULL* |  |
| **sender_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **sender_type** | `varchar(255)` | YES |  | *NULL* |  |
| **target_snapshot** | `longtext` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `offer_subscription_plan`

### Relationships (Foreign Keys):
- **`offer_id`** points to **`offers.id`**
- **`subscription_plan_id`** points to **`subscription_plans.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **offer_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **subscription_plan_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `offers`

### Relationships (Foreign Keys):
- **`branch_id`** points to **`branches.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **branch_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **description** | `text` | YES |  | *NULL* |  |
| **price** | `decimal(10,2)` | NO |  | *NULL* |  |
| **start_date** | `date` | YES |  | *NULL* |  |
| **end_date** | `date` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_by** | `bigint(20) unsigned` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `payslip_adjustments`

### Relationships (Foreign Keys):
- **`payslip_id`** points to **`payslips.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **payslip_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **type** | `enum('bonus','deduction')` | NO |  | *NULL* |  |
| **amount** | `decimal(10,2)` | NO |  | *NULL* |  |
| **reason** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `person_contacts`

### Relationships (Foreign Keys):
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **name** | `varchar(255)` | NO |  | *NULL* |  |
| **phone_number** | `varchar(255)` | NO |  | *NULL* |  |
| **country_code** | `varchar(5)` | YES |  | *NULL* |  |
| **relation** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `person_qr_codes`

### Relationships (Foreign Keys):
- **`person_id`** points to **`people.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **person_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **code** | `varchar(100)` | NO | UNI | *NULL* |  |
| **day_of_week** | `tinyint(3) unsigned` | NO |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `player_unavailabilities`

### Relationships (Foreign Keys):
- **`member_id`** points to **`members.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **day_of_week** | `tinyint(4)` | NO |  | *NULL* |  |
| **start_time** | `time` | NO |  | *NULL* |  |
| **end_time** | `time` | NO |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `qr_access_logs`

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **member_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **token_signature** | `varchar(255)` | NO | MUL | *NULL* |  |
| **used_at** | `timestamp` | NO |  | `current_timestamp()` | on update current_timestamp() |
| **is_successful** | `tinyint(1)` | NO |  | `1` |  |
| **failure_reason** | `varchar(255)` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `session_exceptions`

### Relationships (Foreign Keys):
- **`sport_session_template_id`** points to **`sport_session_templates.id`**
- **`coach_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **sport_session_template_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **coach_id** | `bigint(20) unsigned` | YES | MUL | *NULL* |  |
| **date** | `date` | NO |  | *NULL* |  |
| **status** | `varchar(50)` | NO |  | `canceled` |  |
| **reason** | `text` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `staff_contracts`

### Relationships (Foreign Keys):
- **`staff_id`** points to **`staff.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **staff_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **employment_type** | `varchar(50)` | YES |  | *NULL* |  |
| **base_salary** | `decimal(12,2)` | NO |  | `0.00` |  |
| **commission_type** | `varchar(50)` | YES |  | *NULL* |  |
| **commission_rate** | `decimal(5,2)` | NO |  | `0.00` |  |
| **start_date** | `date` | NO |  | *NULL* |  |
| **end_date** | `date` | YES |  | *NULL* |  |
| **is_active** | `tinyint(1)` | NO |  | `1` |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

## Table: `user_devices`

### Relationships (Foreign Keys):
- **`user_id`** points to **`authentication_users.id`**

| Field | Type | Null | Key | Default | Extra |
| --- | --- | --- | --- | --- | --- |
| **id** | `bigint(20) unsigned` | NO | PRI | *NULL* | auto_increment |
| **user_id** | `bigint(20) unsigned` | NO | MUL | *NULL* |  |
| **fcm_token** | `varchar(255)` | NO | UNI | *NULL* |  |
| **device_info** | `longtext` | YES |  | *NULL* |  |
| **created_at** | `timestamp` | YES |  | *NULL* |  |
| **updated_at** | `timestamp` | YES |  | *NULL* |  |
| **deleted_at** | `timestamp` | YES |  | *NULL* |  |

---

