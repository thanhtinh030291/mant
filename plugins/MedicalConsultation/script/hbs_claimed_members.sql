SELECT DISTINCT
    RTRIM(memb.mbr_last_name || ' ' || memb.mbr_first_name) "mbr_name",
    TO_CHAR(memb.dob, 'YYYY-MM-DD') "dob",
    FN_GET_SYS_CODE_DESC(memb.scma_oid_sex, 'en') "gender",
    RTRIM(poho.poho_name_1 || ' ' || poho.poho_name_2) "poho_name",
    (
        REPLACE(pocy.pocy_no, SUBSTR(pocy.pocy_no, -8), '') || '-' ||
        SUBSTR(pocy.pocy_no, -8, 3) || '-' ||
        SUBSTR(pocy.pocy_no, -5)
    ) "pocy_no",
    (
        SUBSTR(memb.mbr_no, -9, 7) || '-' ||
        SUBSTR(memb.mbr_no, -2)
    ) "mbr_no",
    TO_CHAR(pocy.eff_date, 'YYYY-MM-DD') "pocy_eff_date",
    TO_CHAR(memb.eff_date, 'YYYY-MM-DD') "memb_eff_date",
    popl.pocy_plan_desc "pocy_plan_desc",
    RTRIM(brkr.brkr_name_1 || ' ' || brkr.brkr_name_2) "broker",
    RTRIM(frln.brkr_name_1 || ' ' || frln.brkr_name_2) "frontliner",
    meev.event_desc "event"
FROM mr_member memb
    INNER JOIN mr_policyholder poho
            ON memb.poho_oid = poho.poho_oid
    INNER JOIN mr_member_plan mepl
            ON mepl.memb_oid = memb.memb_oid
    INNER JOIN mr_policy_plan popl
            ON mepl.popl_oid = popl.popl_oid
    INNER JOIN mr_policy pocy
            ON popl.pocy_oid = pocy.pocy_oid
    INNER JOIN cm_broker brkr
            ON pocy.brkr_oid_pri_corr = brkr.brkr_oid
     LEFT JOIN cm_broker frln
            ON pocy.brkr_oid_front_liner = frln.brkr_oid
     LEFT JOIN mr_member_event meev
            ON meev.memb_oid = memb.memb_oid
WHERE memb.memb_oid = TO_NUMBER(?)
  AND popl.popl_oid = TO_NUMBER(?)