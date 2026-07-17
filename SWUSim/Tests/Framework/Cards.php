<?php

class Cards {
    // ── Bases — Common ───────────────────────────────────────────
    const BASES_COMMON_BLUE_30HP              = 'SOR_020';
    const BASES_COMMON_GREEN_30HP             = 'SOR_024';
    const BASES_COMMON_RED_30HP               = 'SOR_026';
    const BASES_COMMON_YELLOW_30HP            = 'SOR_029';
    const BASES_COMMON_BLUE_28HP_FORCE        = 'LOF_020';
    const BASES_COMMON_GREEN_28HP_FORCE       = 'LOF_023';
    const BASES_COMMON_RED_28HP_FORCE         = 'LOF_026';
    const BASES_COMMON_YELLOW_28HP_FORCE      = 'LOF_029';
    const BASES_COMMON_BLUE_27HP_SPLASH       = 'LAW_020';
    const BASES_COMMON_GREEN_27HP_SPLASH      = 'LAW_022';
    const BASES_COMMON_RED_27HP_SPLASH        = 'LAW_025';
    const BASES_COMMON_YELLOW_27HP_SPLASH     = 'LAW_028';

    // ── Bases — SOR ──────────────────────────────────────────────
    const BASES_SOR_SECURITY_COMPLEX          = 'SOR_019'; // 30 HP, Villainy
    const BASES_SOR_COMMAND_CENTER            = 'SOR_023'; // 30 HP, Command
    const BASES_SOR_ECHO_BASE                 = 'SOR_024'; // 30 HP, Command
    const BASES_SOR_ENERGY_CONVERSION_LAB     = 'SOR_022'; // 25 HP, Command
    const BASES_SOR_TARKINTOWN                = 'SOR_025'; // 25 HP, Aggression
    const BASES_SOR_JEDHA_CITY                = 'SOR_028'; // 30 HP, Vigilance

    // ── Bases — TWI ──────────────────────────────────────────────
    const BASES_TWI_PAU_CITY                  = 'TWI_019';
    const BASES_TWI_DROID_MANUFACTORY         = 'TWI_022';
    const BASES_TWI_SHADOW_COLLECTIVE_CAMP    = 'TWI_025';
    const BASES_TWI_PETRANAKI_ARENA           = 'TWI_028';

    // ── Bases — JTL ──────────────────────────────────────────────
    const BASES_JTL_COLOSSUS                  = 'JTL_021';
    const BASES_JTL_DATA_VAULT                = 'JTL_024';
    const BASES_JTL_THERMAL_OSCILLATOR        = 'JTL_025';
    const BASES_JTL_NABAT_VILLAGE             = 'JTL_028';
    const BASES_JTL_LAKE_COUNTRY              = 'JTL_031';

    // ── Bases — LOF ──────────────────────────────────────────────
    const BASES_LOF_VERGENCE_TEMPLE           = 'LOF_019';
    const BASES_LOF_MYSTIC_MONASTERY          = 'LOF_022';
    const BASES_LOF_TEMPLE_OF_DESTRUCTION     = 'LOF_025';
    const BASES_LOF_TOMB_OF_EILRAM            = 'LOF_028';

    // ── Bases — LAW ──────────────────────────────────────────────
    const BASES_LAW_ALLIANCE_OUTPOST          = 'LAW_019';
    const BASES_LAW_GREAT_PIT_OF_CARKOON      = 'LAW_023';
    const BASES_LAW_SHIPBREAKING_YARD         = 'LAW_026';
    const BASES_LAW_CITADEL_RESEARCH_CENTER   = 'LAW_029';

    // ── Leaders — SOR ────────────────────────────────────────────
    const LEADERS_SOR_DIRECTOR_KRENNIC        = 'SOR_001';
    const LEADERS_SOR_IDEN_VERSIO             = 'SOR_002';
    const LEADERS_SOR_CHEWBACCA               = 'SOR_003';
    const LEADERS_SOR_CHIRRUT_IMWE            = 'SOR_004';
    const LEADERS_SOR_LUKE_SKYWALKER          = 'SOR_005';
    const LEADERS_SOR_EMPEROR_PALPATINE       = 'SOR_006';
    const LEADERS_SOR_GRAND_MOFF_TARKIN       = 'SOR_007';
    const LEADERS_SOR_HERA_SYNDULLA           = 'SOR_008';
    const LEADERS_SOR_LEIA_ORGANA             = 'SOR_009';
    const LEADERS_SOR_DARTH_VADER             = 'SOR_010';
    const LEADERS_SOR_GRAND_INQUISITOR        = 'SOR_011';
    const LEADERS_SOR_IG88                    = 'SOR_012';
    const LEADERS_SOR_CASSIAN_ANDOR           = 'SOR_013';
    const LEADERS_SOR_SABINE_WREN             = 'SOR_014';
    const LEADERS_SOR_BOBA_FETT               = 'SOR_015';
    const LEADERS_SOR_GRAND_ADMIRAL_THRAWN    = 'SOR_016';
    const LEADERS_SOR_HAN_SOLO                = 'SOR_017';
    const LEADERS_SOR_JYN_ERSO                = 'SOR_018';

    // ── Leaders — SHD ────────────────────────────────────────────
    const LEADERS_SHD_GAR_SAXON               = 'SHD_001';
    const LEADERS_SHD_QIRA                    = 'SHD_002';
    const LEADERS_SHD_FINN                    = 'SHD_003';
    const LEADERS_SHD_REY                     = 'SHD_004';
    const LEADERS_SHD_HONDO_OHNAKA            = 'SHD_005';
    const LEADERS_SHD_JABBA_THE_HUTT          = 'SHD_006';
    const LEADERS_SHD_MOFF_GIDEON             = 'SHD_007';
    const LEADERS_SHD_BOBA_FETT               = 'SHD_008';
    const LEADERS_SHD_HUNTER                  = 'SHD_009';
    const LEADERS_SHD_BOSSK                   = 'SHD_010';
    const LEADERS_SHD_KYLO_REN                = 'SHD_011';
    const LEADERS_SHD_BO_KATAN_KRYZE          = 'SHD_012';
    const LEADERS_SHD_HAN_SOLO                = 'SHD_013';
    const LEADERS_SHD_CAD_BANE                = 'SHD_014';
    const LEADERS_SHD_DOCTOR_APHRA            = 'SHD_015';
    const LEADERS_SHD_FENNEC_SHAND            = 'SHD_016';
    const LEADERS_SHD_LANDO_CALRISSIAN        = 'SHD_017';
    const LEADERS_SHD_THE_MANDALORIAN         = 'SHD_018';

    // ── Leaders — TWI ────────────────────────────────────────────
    const LEADERS_TWI_NALA_SE                 = 'TWI_001';
    const LEADERS_TWI_NUTE_GUNRAY             = 'TWI_002';
    const LEADERS_TWI_OBI_WAN_KENOBI          = 'TWI_003';
    const LEADERS_TWI_YODA                    = 'TWI_004';
    const LEADERS_TWI_COUNT_DOOKU             = 'TWI_005';
    const LEADERS_TWI_WAT_TAMBOR              = 'TWI_006';
    const LEADERS_TWI_CAPTAIN_REX             = 'TWI_007';
    const LEADERS_TWI_PADME_AMIDALA           = 'TWI_008';
    const LEADERS_TWI_MAUL                    = 'TWI_009';
    const LEADERS_TWI_PRE_VIZSLA              = 'TWI_010';
    const LEADERS_TWI_AHSOKA_TANO             = 'TWI_011';
    const LEADERS_TWI_ANAKIN_SKYWALKER        = 'TWI_012';
    const LEADERS_TWI_MACE_WINDU              = 'TWI_013';
    const LEADERS_TWI_ASAJJ_VENTRESS          = 'TWI_014';
    const LEADERS_TWI_GENERAL_GRIEVOUS        = 'TWI_015';
    const LEADERS_TWI_JANGO_FETT              = 'TWI_016';
    const LEADERS_TWI_CHANCELLOR_PALPATINE    = 'TWI_017';
    const LEADERS_TWI_QUINLAN_VOS             = 'TWI_018';

    // ── Leaders — JTL ────────────────────────────────────────────
    const LEADERS_JTL_ASAJJ_VENTRESS          = 'JTL_001';
    const LEADERS_JTL_GRAND_ADMIRAL_THRAWN    = 'JTL_002';
    const LEADERS_JTL_LANDO_CALRISSIAN        = 'JTL_003';
    const LEADERS_JTL_ROSE_TICO               = 'JTL_004';
    const LEADERS_JTL_ADMIRAL_PIETT           = 'JTL_005';
    const LEADERS_JTL_DARTH_VADER             = 'JTL_006';
    const LEADERS_JTL_ADMIRAL_HOLDO           = 'JTL_007';
    const LEADERS_JTL_WEDGE_ANTILLES          = 'JTL_008';
    const LEADERS_JTL_BOBA_FETT               = 'JTL_009';
    const LEADERS_JTL_CAPTAIN_PHASMA          = 'JTL_010';
    const LEADERS_JTL_MAJOR_VONREG            = 'JTL_011';
    const LEADERS_JTL_LUKE_SKYWALKER          = 'JTL_012';
    const LEADERS_JTL_POE_DAMERON             = 'JTL_013';
    const LEADERS_JTL_ADMIRAL_TRENCH          = 'JTL_014';
    const LEADERS_JTL_RIO_DURANT              = 'JTL_015';
    const LEADERS_JTL_ADMIRAL_ACKBAR          = 'JTL_016';
    const LEADERS_JTL_HAN_SOLO                = 'JTL_017';
    const LEADERS_JTL_KAZUDA_XIONO            = 'JTL_018';

    // ── Leaders — LOF ────────────────────────────────────────────
    const LEADERS_LOF_KYLO_REN                = 'LOF_001';
    const LEADERS_LOF_MOTHER_TALZIN           = 'LOF_002';
    const LEADERS_LOF_AHSOKA_TANO             = 'LOF_003';
    const LEADERS_LOF_KANAN_JARRUS            = 'LOF_004';
    const LEADERS_LOF_MORGAN_ELSBETH          = 'LOF_005';
    const LEADERS_LOF_SUPREME_LEADER_SNOKE    = 'LOF_006';
    const LEADERS_LOF_AVAR_KRISS              = 'LOF_007';
    const LEADERS_LOF_OBI_WAN_KENOBI          = 'LOF_008';
    const LEADERS_LOF_DARTH_MAUL              = 'LOF_009';
    const LEADERS_LOF_THIRD_SISTER            = 'LOF_010';
    const LEADERS_LOF_KIT_FISTO               = 'LOF_011';
    const LEADERS_LOF_REY                     = 'LOF_012';
    const LEADERS_LOF_BARRISS_OFFEE           = 'LOF_013';
    const LEADERS_LOF_GRAND_INQUISITOR        = 'LOF_014';
    const LEADERS_LOF_CAL_KESTIS              = 'LOF_015';
    const LEADERS_LOF_QUI_GON_JINN            = 'LOF_016';
    const LEADERS_LOF_DARTH_REVAN             = 'LOF_017';
    const LEADERS_LOF_ANAKIN_SKYWALKER        = 'LOF_018';

    // ── Leaders — LAW ────────────────────────────────────────────
    const LEADERS_LAW_SAW_GERRERA             = 'LAW_001';
    const LEADERS_LAW_TOBIAS_BECKETT          = 'LAW_002';
    const LEADERS_LAW_AGENT_KALLUS            = 'LAW_003';
    const LEADERS_LAW_AURRA_SING              = 'LAW_004';
    const LEADERS_LAW_JYN_ERSO                = 'LAW_005';
    const LEADERS_LAW_VEL_SARTHA              = 'LAW_006';
    const LEADERS_LAW_BOBA_FETT               = 'LAW_007';
    const LEADERS_LAW_DIRECTOR_KRENNIC        = 'LAW_008';
    const LEADERS_LAW_HERA_SYNDULLA           = 'LAW_009';
    const LEADERS_LAW_LEIA_ORGANA             = 'LAW_010';
    const LEADERS_LAW_DARTH_VADER             = 'LAW_011';
    const LEADERS_LAW_SEBULBA                 = 'LAW_012';
    const LEADERS_LAW_CHEWBACCA               = 'LAW_013';
    const LEADERS_LAW_ENFYS_NEST              = 'LAW_014';
    const LEADERS_LAW_JABBA_THE_HUTT          = 'LAW_015';
    const LEADERS_LAW_THE_CLIENT              = 'LAW_016';
    const LEADERS_LAW_HAN_SOLO                = 'LAW_017';
    const LEADERS_LAW_LANDO_CALRISSIAN        = 'LAW_018';

    // ── Leaders — SEC ────────────────────────────────────────────
    const LEADERS_SEC_CHANCELLOR_PALPATINE    = 'SEC_001';
    const LEADERS_SEC_JABBA_THE_HUTT          = 'SEC_002';
    const LEADERS_SEC_LAMA_SU                 = 'SEC_003';
    const LEADERS_SEC_LEIA_ORGANA             = 'SEC_004';
    const LEADERS_SEC_SATINE_KRYZE            = 'SEC_005';
    const LEADERS_SEC_COLONEL_YULAREN         = 'SEC_006';
    const LEADERS_SEC_DRYDEN_VOS              = 'SEC_007';
    const LEADERS_SEC_BAIL_ORGANA             = 'SEC_008';
    const LEADERS_SEC_MON_MOTHMA              = 'SEC_009';
    const LEADERS_SEC_DEDRA_MEERO             = 'SEC_010';
    const LEADERS_SEC_GOVERNOR_PRYCE          = 'SEC_011';
    const LEADERS_SEC_CASSIAN_ANDOR           = 'SEC_012';
    const LEADERS_SEC_LUTHEN_RAEL             = 'SEC_013';
    const LEADERS_SEC_SLY_MOORE               = 'SEC_014';
    const LEADERS_SEC_C3PO                    = 'SEC_015';
    const LEADERS_SEC_PADME_AMIDALA           = 'SEC_016';
    const LEADERS_SEC_SABE                    = 'SEC_017';
    const LEADERS_SEC_DJ                      = 'SEC_018';

    // ── Leaders — IBH ────────────────────────────────────────────
    const LEADERS_IBH_LEIA_ORGANA             = 'IBH_001';
    const LEADERS_IBH_DARTH_VADER             = 'IBH_053';

    // ── Leaders — TS26 ───────────────────────────────────────────
    const LEADERS_TS26_COUNT_DOOKU            = 'TS26_01';
    const LEADERS_TS26_ANAKIN_SKYWALKER       = 'TS26_02';
    const LEADERS_TS26_MAUL                   = 'TS26_03';
    const LEADERS_TS26_PADME_AMIDALA          = 'TS26_04';
    const LEADERS_TS26_SAVAGE_OPRESS          = 'TS26_05';
    const LEADERS_TS26_REX                    = 'TS26_06';
    const LEADERS_TS26_ASAJJ_VENTRESS         = 'TS26_07';
    const LEADERS_TS26_AHSOKA_TANO            = 'TS26_08';

    // ── Units — Token ────────────────────────────────────────────
    const UNITS_TOKEN_BATTLE_DROID            = 'TWI_T01';
    const UNITS_TOKEN_CLONE_TROOPER           = 'TWI_T02';
    const UNITS_TOKEN_TIE_FIGHTER             = 'JTL_T01';
    const UNITS_TOKEN_X_WING                  = 'JTL_T02';
    const UNITS_TOKEN_SPY                     = 'SEC_T01';

    // ── Units — SOR ──────────────────────────────────────────────
    const UNITS_SOR_INFERNO_FOUR              = 'SOR_031';
    const UNITS_SOR_DEATH_TROOPER             = 'SOR_033';
    const UNITS_SOR_DEL_MEEKO                 = 'SOR_034';
    const UNITS_SOR_LIEUTENANT_CHILDSEN       = 'SOR_035';
    const UNITS_SOR_GIDEON_HASK               = 'SOR_036';
    const UNITS_SOR_ACADEMY_DEFENSE_WALKER    = 'SOR_037';
    const UNITS_SOR_COUNT_DOOKU               = 'SOR_038';
    const UNITS_SOR_ATAT_SUPPRESSOR           = 'SOR_039';
    const UNITS_SOR_AVENGER                   = 'SOR_040';
    const UNITS_SOR_YODA                      = 'SOR_045';
    const UNITS_SOR_KANAN_JARRUS              = 'SOR_047';
    const UNITS_SOR_OBI_WAN_KENOBI            = 'SOR_049'; // 4 power / 6 HP / cost 6
    const UNITS_SOR_THE_GHOST                 = 'SOR_050';
    const UNITS_SOR_LUKE_SKYWALKER            = 'SOR_051';
    const UNITS_SOR_REDEMPTION                = 'SOR_052';
    const UNITS_SOR_BENDU                     = 'SOR_056';
    const UNITS_SOR_SURGICAL_DROID            = 'SOR_059';
    const UNITS_SOR_DISTANT_PATROLLER         = 'SOR_060';
    const UNITS_SOR_GUARDIAN_OF_THE_WHILLS    = 'SOR_061';
    const UNITS_SOR_REGIONAL_GOVERNOR         = 'SOR_062';
    const UNITS_SOR_SYSTEM_PATROL_CRAFT       = 'SOR_066';
    const UNITS_SOR_RUGGED_SURVIVORS          = 'SOR_067';
    const UNITS_SOR_LOM_PYKE                  = 'SOR_068';
    const UNITS_SOR_GENERAL_TAGGE             = 'SOR_080';
    const UNITS_SOR_SEASONED_SHORETROOPER     = 'SOR_081';
    const UNITS_SOR_SUPERLASER_TECHNICIAN     = 'SOR_083'; // 2 power / 1 HP / cost 3
    const UNITS_SOR_GRAND_MOFF_TARKIN         = 'SOR_084';
    const UNITS_SOR_RUKH                      = 'SOR_085';
    const UNITS_SOR_GLADIATOR_STAR_DESTROYER  = 'SOR_086';
    const UNITS_SOR_DARTH_VADER               = 'SOR_087';
    const UNITS_SOR_BLIZZARD_ASSAULT_ATAT     = 'SOR_088';
    const UNITS_SOR_RELENTLESS                = 'SOR_089';
    const UNITS_SOR_DEVASTATOR                = 'SOR_090';
    const UNITS_SOR_ALLIANCE_DISPATCHER       = 'SOR_093';
    const UNITS_SOR_BAIL_ORGANA               = 'SOR_094';
    const UNITS_SOR_BATTLEFIELD_MARINE        = 'SOR_095';
    const UNITS_SOR_MON_MOTHMA                = 'SOR_096';
    const UNITS_SOR_ECHO_BASE_DEFENDER        = 'SOR_098';
    const UNITS_SOR_BRIGHT_HOPE               = 'SOR_099';
    const UNITS_SOR_WEDGE_ANTILLES            = 'SOR_100';
    const UNITS_SOR_ROGUE_SQUADRON_SKIRMISHER = 'SOR_101';
    const UNITS_SOR_HOME_ONE                  = 'SOR_102';
    const UNITS_SOR_GENERAL_KRELL             = 'SOR_105';
    const UNITS_SOR_VANGUARD_INFANTRY         = 'SOR_108';
    const UNITS_SOR_COLONEL_YULAREN           = 'SOR_109';
    const UNITS_SOR_FRONTLINE_SHUTTLE         = 'SOR_110';
    const UNITS_SOR_PATROLLING_V_WING         = 'SOR_111';
    const UNITS_SOR_HOMESTEAD_MILITIA         = 'SOR_113';
    const UNITS_SOR_97TH_LEGION               = 'SOR_118';
    const UNITS_SOR_REINFORCEMENT_WALKER      = 'SOR_119';
    const UNITS_SOR_DEATH_STAR_STORMTROOPER   = 'SOR_128';
    const UNITS_SOR_ADMIRAL_OZZEL             = 'SOR_129';
    const UNITS_SOR_IMPERIAL_INTERCEPTOR      = 'SOR_132';
    const UNITS_SOR_SEVENTH_SISTER            = 'SOR_133';
    const UNITS_SOR_RUTHLESS_RAIDER           = 'SOR_134';
    const UNITS_SOR_EMPEROR_PALPATINE         = 'SOR_135';
    const UNITS_SOR_SPEC_FORCE_SOLDIER        = 'SOR_140';
    const UNITS_SOR_EXPLOSIVES_ARTIST         = 'SOR_142';
    const UNITS_SOR_FIGHTERS_FOR_FREEDOM      = 'SOR_143';
    const UNITS_SOR_K2SO                      = 'SOR_145';
    const UNITS_SOR_ZEB_ORRELIOS              = 'SOR_146';
    const UNITS_SOR_BLACK_ONE                 = 'SOR_147';
    const UNITS_SOR_GUERILLA_ATTACK_POD       = 'SOR_148';
    const UNITS_SOR_SAW_GERRERA               = 'SOR_153';
    const UNITS_SOR_JEDHA_AGITATOR            = 'SOR_158';
    const UNITS_SOR_ARDENT_SYMPATHIZER        = 'SOR_161';
    const UNITS_SOR_STAR_WING_SCOUT           = 'SOR_163';
    const UNITS_SOR_WAMPA                     = 'SOR_164';
    const UNITS_SOR_JABBA_THE_HUTT            = 'SOR_181';
    const UNITS_SOR_BOUNTY_HUNTER_CREW        = 'SOR_183';
    const UNITS_SOR_CHOPPER                   = 'SOR_188';
    const UNITS_SOR_LEIA_ORGANA               = 'SOR_189'; // 2 power / 2 HP / cost 2
    const UNITS_SOR_LOTHAL_INSURGENT          = 'SOR_190';
    const UNITS_SOR_VANGUARD_ACE              = 'SOR_191';
    const UNITS_SOR_EZRA_BRIDGER              = 'SOR_192';
    const UNITS_SOR_MILLENNIUM_FALCON         = 'SOR_193';
    const UNITS_SOR_LANDO_CALRISSIAN          = 'SOR_197';
    const UNITS_SOR_BODHI_ROOK                = 'SOR_201';
    const UNITS_SOR_CANTINA_BOUNCER           = 'SOR_202';
    const UNITS_SOR_GREEDO                    = 'SOR_204';
    const UNITS_SOR_MINING_GUILD_TIE          = 'SOR_206';
    const UNITS_SOR_CRAFTY_SMUGGLER           = 'SOR_207';
    const UNITS_SOR_OUTER_RIM_HEADHUNTER      = 'SOR_208';
    const UNITS_SOR_PIRATED_STARFIGHTER       = 'SOR_209';
    const UNITS_SOR_GAMORREAN_GUARDS          = 'SOR_211';
    const UNITS_SOR_STRAFING_GUNSHIP          = 'SOR_212';
    const UNITS_SOR_SYNDICATE_LACKEYS         = 'SOR_213';
    const UNITS_SOR_VIPER_PROBE_DROID         = 'SOR_228';
    const UNITS_SOR_CELL_BLOCK_GUARD          = 'SOR_229'; // 3 power / 3 HP / cost 3
    const UNITS_SOR_GENERAL_VEERS             = 'SOR_230';
    const UNITS_SOR_TIE_ADVANCED              = 'SOR_231';
    const UNITS_SOR_R2D2                      = 'SOR_236';
    const UNITS_SOR_REBEL_PATHFINDER          = 'SOR_239';
    const UNITS_SOR_FLEET_LIEUTENANT          = 'SOR_240'; // 3 power / 3 HP / cost 3
    const UNITS_SOR_WING_LEADER               = 'SOR_241';
    const UNITS_SOR_GENERAL_DODONNA           = 'SOR_242';
    const UNITS_SOR_SNOWSPEEDER               = 'SOR_244';

    // ── Units — SHD ──────────────────────────────────────────────
    const UNITS_SHD_HYLOBON_ENFORCER          = 'SHD_027';
    const UNITS_SHD_DOCTOR_PERSHING           = 'SHD_028';
    const UNITS_SHD_SUPERCOMMANDO_SQUAD       = 'SHD_034';
    const UNITS_SHD_THE_MANDALORIAN           = 'SHD_049';
    const UNITS_SHD_CHEWBACCA_PYKESBANE       = 'SHD_050';
    const UNITS_SHD_SUGI                      = 'SHD_052';
    const UNITS_SHD_FOLLOWER_OF_THE_WAY       = 'SHD_056';
    const UNITS_SHD_GENERAL_TAGGE             = 'SHD_081';
    const UNITS_SHD_WARBIRD_STOWAWAY          = 'SHD_086';
    const UNITS_SHD_SUNDARI_PEACE_KEEPER      = 'SHD_098';
    const UNITS_SHD_RECKLESS_GUNSLINGER       = 'SHD_160';
    const UNITS_SHD_FOUR_LOM                  = 'SHD_188';
    const UNITS_SHD_ZUCKUSS                   = 'SHD_190';
    const UNITS_SHD_DJ_BLATANT_THIEF          = 'SHD_213';
    const UNITS_SHD_TOBIAS_BECKETT            = 'SHD_217';
    const UNITS_SHD_TECH                      = 'SHD_248';

    // ── Units — JTL ──────────────────────────────────────────────
    const UNITS_JTL_L337                      = 'JTL_049';
    const UNITS_JTL_KIJIMI_PATROLLERS         = 'JTL_082';
    const UNITS_JTL_LUKE_SKYWALKER            = 'JTL_094';
    const UNITS_JTL_BLUE_LEADER               = 'JTL_096';
    const UNITS_JTL_SNAP_WEXLEY               = 'JTL_098';
    const UNITS_JTL_REBELLIOUS_HAMMERHEAD     = 'JTL_153';
    const UNITS_JTL_R2D2                      = 'JTL_245';
    const UNITS_JTL_MILLENNIUM_FALCON         = 'JTL_249';

    // ── Units — LOF ──────────────────────────────────────────────
    const UNITS_LOF_PRIESTESSES_OF_THE_FORCE  = 'LOF_072';
    const UNITS_LOF_GUNGI                     = 'LOF_093';
    const UNITS_LOF_KELLERAN_BEQ              = 'LOF_100';
    const UNITS_LOF_STRIKESHIP                = 'LOF_131';
    const UNITS_LOF_WITCH_OF_THE_MIST         = 'LOF_154';
    const UNITS_LOF_THE_LEGACY_RUN            = 'LOF_213';
    const UNITS_LOF_SANDTROOPER_CAVALRY       = 'LOF_232';
    const UNITS_LOF_GROGU                     = 'LOF_246';

    // ── Units — TWI ──────────────────────────────────────────────
    const UNITS_TWI_ADMIRAL_TRENCH            = 'TWI_086';
    const UNITS_TWI_INFILTRATING_DEMOLISHER   = 'TWI_182';
    const UNITS_TWI_SAN_HILL                  = 'TWI_186';
    const UNITS_TWI_R2D2_FULL_OF_SOLUTIONS    = 'TWI_193';

    // ── Units — SEC ──────────────────────────────────────────────
    const UNITS_SEC_CAD_BANE                  = 'SEC_034';
    const UNITS_SEC_NALA_SE                   = 'SEC_065';
    const UNITS_SEC_CHANCELLOR_PALPATINE      = 'SEC_082';
    const UNITS_SEC_ISB_SHUTTLE               = 'SEC_083';
    const UNITS_SEC_VICE_ADMIRAL_RAMPART      = 'SEC_085';

    // ── Units — ASH ──────────────────────────────────────────────
    const UNITS_ASH_259                        = 'ASH_259'; // When Played: You may deal 1 damage to a ground unit.

    // ── Upgrades — Token ─────────────────────────────────────────
    const UPGRADES_TOKEN_EXPERIENCE           = 'SOR_T01';
    const UPGRADES_TOKEN_SHIELD               = 'SOR_T02';

    // ── Upgrades — SOR ───────────────────────────────────────────
    const UPGRADES_SOR_LUKES_LIGHTSABER       = 'SOR_053';
    const UPGRADES_SOR_JEDI_LIGHTSABER        = 'SOR_054';
    const UPGRADES_SOR_PROTECTOR              = 'SOR_057';
    const UPGRADES_SOR_ELECTROSTAFF           = 'SOR_071';
    const UPGRADES_SOR_ENTRENCHED             = 'SOR_072';
    const UPGRADES_SOR_ACADEMY_TRAINING       = 'SOR_120';
    const UPGRADES_SOR_HARDPOINT_HEAVY_BLASTER = 'SOR_121';
    const UPGRADES_SOR_TRAITOROUS             = 'SOR_122';
    const UPGRADES_SOR_VADERS_LIGHTSABER      = 'SOR_136';
    const UPGRADES_SOR_FALLEN_LIGHTSABER      = 'SOR_137';
    const UPGRADES_SOR_SMUGGLING_COMPARTMENT  = 'SOR_214';

    // ── Upgrades — SHD ───────────────────────────────────────────
    const UPGRADES_SHD_PUBLIC_ENEMY           = 'SHD_068';
    const UPGRADES_SHD_IMPRISONED             = 'SHD_072';
    const UPGRADES_SHD_MANDALORIAN_ARMOR      = 'SHD_073';
    const UPGRADES_SHD_LEGAL_AUTHORITY        = 'SHD_124';
    const UPGRADES_SHD_THE_DARKSABER          = 'SHD_126';
    const UPGRADES_SHD_HOTSHOT_BLASTER        = 'SHD_174';
    const UPGRADES_SHD_VAMBRACE_FLAMETHROWER  = 'SHD_177';

    // ── Upgrades — LOF ───────────────────────────────────────────
    const UPGRADES_LOF_BOLSTERED_ENDURANCE    = 'LOF_074';
    const UPGRADES_LOF_CONSTRUCTED_LIGHTSABER = 'LOF_261';

    // ── Events — SOR ─────────────────────────────────────────────
    const EVENTS_SOR_POWER_OF_THE_DARK_SIDE   = 'SOR_041';
    const EVENTS_SOR_SEARCH_YOUR_FEELINGS     = 'SOR_042';
    const EVENTS_SOR_SUPERLASER_BLAST         = 'SOR_043';
    const EVENTS_SOR_THE_FORCE_IS_WITH_ME     = 'SOR_055';
    const EVENTS_SOR_REPAIR                   = 'SOR_074';
    const EVENTS_SOR_MOMENT_OF_PEACE          = 'SOR_073';
    const EVENTS_SOR_IT_BINDS_ALL_THINGS      = 'SOR_075';
    const EVENTS_SOR_MAKE_AN_OPENING          = 'SOR_076';
    const EVENTS_SOR_TAKEDOWN                 = 'SOR_077';
    const EVENTS_SOR_VANQUISH                 = 'SOR_078';
    const EVENTS_SOR_EMPERORS_LEGION          = 'SOR_091';
    const EVENTS_SOR_OVERWHELMING_BARRAGE     = 'SOR_092';
    const EVENTS_SOR_REBEL_ASSAULT            = 'SOR_103';
    const EVENTS_SOR_U_WING_REINFORCEMENT     = 'SOR_104';
    const EVENTS_SOR_ATTACK_PATTERN_DELTA     = 'SOR_106';
    const EVENTS_SOR_RECRUIT                  = 'SOR_123';
    const EVENTS_SOR_TACTICAL_ADVANTAGE       = 'SOR_124';
    const EVENTS_SOR_PREPARE_FOR_TAKEOFF      = 'SOR_125';
    const EVENTS_SOR_RESUPPLY                 = 'SOR_126';
    const EVENTS_SOR_STRIKE_TRUE              = 'SOR_127';
    const EVENTS_SOR_FORCE_CHOKE              = 'SOR_139';
    const EVENTS_SOR_HEROIC_SACRIFICE         = 'SOR_150';
    const EVENTS_SOR_KARABAST                 = 'SOR_151';
    const EVENTS_SOR_FOR_A_CAUSE_I_BELIEVE_IN = 'SOR_152';
    const EVENTS_SOR_KEEP_FIGHTING            = 'SOR_169';
    const EVENTS_SOR_POWER_FAILURE            = 'SOR_170';
    const EVENTS_SOR_MISSION_BRIEFING         = 'SOR_171';
    const EVENTS_SOR_OPEN_FIRE                = 'SOR_172';
    const EVENTS_SOR_BOMBING_RUN              = 'SOR_173';
    const EVENTS_SOR_SMOKE_AND_CINDERS        = 'SOR_174';
    const EVENTS_SOR_FORCED_SURRENDER         = 'SOR_175';
    const EVENTS_SOR_NO_GOOD_TO_ME_DEAD       = 'SOR_186';
    const EVENTS_SOR_I_HAD_NO_CHOICE          = 'SOR_187';
    const EVENTS_SOR_BAMBOOZLE                = 'SOR_199';
    const EVENTS_SOR_SPARK_OF_REBELLION       = 'SOR_200';
    const EVENTS_SOR_DISARM                   = 'SOR_216';
    const EVENTS_SOR_SHOOT_FIRST              = 'SOR_217';
    const EVENTS_SOR_ASTEROID_SANCTUARY       = 'SOR_218';
    const EVENTS_SOR_SURPRISE_STRIKE          = 'SOR_220';
    const EVENTS_SOR_OUTMANEUVER              = 'SOR_221';
    const EVENTS_SOR_WAYLAY                   = 'SOR_222';
    const EVENTS_SOR_DONT_GET_COCKY           = 'SOR_223';
    const EVENTS_SOR_CHANGE_OF_HEART          = 'SOR_224';
    const EVENTS_SOR_I_AM_YOUR_FATHER         = 'SOR_233';
    const EVENTS_SOR_MAXIMUM_FIREPOWER        = 'SOR_234';
    const EVENTS_SOR_GALACTIC_AMBITION        = 'SOR_235';
    const EVENTS_SOR_MEDAL_CEREMONY           = 'SOR_245';
    const EVENTS_SOR_CONFISCATE               = 'SOR_251';

    // ── Events — SHD ─────────────────────────────────────────────
    const EVENTS_SHD_MIDNIGHT_REPAIRS         = 'SHD_054';
    const EVENTS_SHD_TIMELY_INTERVENTION      = 'SHD_129';
    const EVENTS_SHD_CHOOSE_SIDES             = 'SHD_132';

    // ── Events — TWI ─────────────────────────────────────────────
    const EVENTS_TWI_VANQUISH                 = 'TWI_077';
    const EVENTS_TWI_CHRISTOPHSIS             = 'TWI_078';
    const EVENTS_TWI_TAKE_CAPTIVE             = 'TWI_128';
    const EVENTS_TWI_DROID_DEPLOYMENT         = 'TWI_237';
    const EVENTS_TWI_DROP_IN                  = 'TWI_251';

    // ── Events — JTL ─────────────────────────────────────────────
    const EVENTS_JTL_REPAIR                   = 'JTL_075';
    const EVENTS_JTL_UNITY_OF_PURPOSE         = 'JTL_106';
    const EVENTS_JTL_TORPEDO_BARRAGE          = 'JTL_234';
    const EVENTS_JTL_DEDICATED_WINGMEN        = 'JTL_254';

    // ── Events — SEC ─────────────────────────────────────────────
    const EVENTS_SEC_I_AM_THE_SENATE          = 'SEC_092';
}
