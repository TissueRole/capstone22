-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 06:04 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `capstone`
--

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `lesson_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `module_id`, `title`, `content`, `lesson_order`, `created_at`) VALUES
(1, 1, 'Definition and Scope', '##What is Urban Agriculture? \r\nUrban agriculture is the practice of cultivating crops, growing food,\r\nand raising livestock in urban and peri-urban settings. It includes producing, processing, and\r\ndistributing agricultural goods within city spaces to address food security, promote sustainable\r\nlivelihoods, and integrate agriculture into the urban economy.\r\n\r\n##Key Features:\r\n- **Utilizes Urban Spaces:** Urban agriculture makes innovative use of spaces like vacant \r\nlots, rooftops, balconies, walls, and even abandoned buildings to grow food and raise animals.\r\n- **Comprehensive Activities:** Beyond farming, it encompasses food processing (turning raw\r\ncrops into consumable products), distribution (transporting produce to consumers), and\r\nmarketing.\r\n- **Scales of Operation**: Practices range from small-scale home gardens to large commercial\r\nurban farms.\r\n\r\n##Categories:\r\n**1. Food Production:** Includes cultivating vegetables, fruits, herbs, aquaponics (combining fish farming\r\nand hydroponics), and hydroponics (growing plants without soil).\r\n\r\n**2. Livestock Production:** Small-scale raising of poultry, rabbits, or goats, and even urban beekeeping.\r\n\r\n**3. Ornamentals:** Growing flowers and landscaping plants for beautifying spaces, increasing\r\nbiodiversity, and promoting mental well-being.', 1, '2025-09-16 07:00:37'),
(2, 1, 'Historical Context', 'Urban agriculture has a long-standing history as a solution to food scarcity, particularly during\r\ntimes of crisis. Understanding its evolution helps recognize its importance in contemporary\r\nurban development.\r\n##Wartime Response (Victory Gardens)\r\nDuring World War II, governments promoted \"**Victory Gardens**\" in cities and\r\nsuburbs to supplement food rations. Citizens grew vegetables and fruits in\r\nbackyards, parks, and rooftops to ensure food security during supply chain\r\ndisruptions.\r\n##Traditional Systems in Asia\r\nUrban farming is deeply rooted in cultures such as India, where rooftop and\r\ncourtyard farming have been practiced for centuries. These systems not only\r\nsupplemented family diets but also reflected sustainable resource use, like\r\nrainwater harvesting.\r\n##Modern Evolution\r\nAs cities grew, urban agriculture adapted with innovations like vertical farming,\r\naquaponics, and greenhouses to maximize limited space while addressing urban\r\nchallenges like food deserts.', 2, '2025-09-16 09:58:12'),
(3, 1, 'Benefits of Urban Agriculture', 'Urban agriculture offers multifaceted benefits that address environmental, social, and economic\r\nchallenges faced by cities today.\r\n\r\n##Environmental Benefits\r\n- **Carbon Sequestration:** Urban farms absorb carbon dioxide, helping mitigate climate\r\nchange. Plants act as natural air purifiers by improving air quality in congested areas.\r\n\r\n- **Reduction of Urban Heat Island Effect:** Rooftop gardens and urban farms reduce heat\r\nbuildup in cities by insulating buildings and lowering surrounding temperatures,\r\ndecreasing energy demands for cooling.\r\n\r\n- **Biodiversity:** Urban gardens create habitats for pollinators like bees and butterflies,\r\nfostering biodiversity in cities.\r\n\r\n##Social Benefits\r\n- **Community Engagement:** Urban farming projects encourage community participation,\r\nempowering residents to work collaboratively. Community gardens foster a sense of\r\nownership and pride.\r\n\r\n- **Improved Well-Being:** Green spaces have proven psychological benefits, reduced stress\r\nand promoting relaxation in densely populated urban areas.\r\n\r\n- **Education and Awareness:** Urban farms serve as educational tools for teaching\r\nsustainable farming practices and healthy eating habits.\r\n\r\n##Economic Benefits\r\n- **Cost Savings:** Growing food locally reduces reliance on expensive imported produce,\r\nsaving households money.\r\n- **Job Creation:** Urban agriculture generates employment opportunities across farming, food\r\nprocessing, marketing, and distribution.\r\n- **Local Entrepreneurship:** Initiatives like farmers\' markets and agri-business startups\r\nencourage entrepreneurship and stimulate local economies.', 3, '2025-09-16 10:22:23'),
(4, 2, 'Site Assessment and Selection', 'Selecting a suitable site is crucial for the success of an urban agriculture project. \r\nFactors such as space, sunlight, water availability, and soil condition must be considered.\r\n\r\n##Key Considerations:\r\n- **Space Availability**\r\n     - Urban agriculture often takes place in unconventional spaces. Potential areas\r\n     include rooftops, balconies, vacant lots, community parks, and even walls.\r\n     - Conduct a survey of available spaces and evaluate their potential for agricultural\r\n     use, including accessibility and structural integrity (for rooftops).\r\n- **Sunlight Exposure**\r\n     - Most vegetable crops require 6-8 hours of direct sunlight daily.\r\n     - Conduct a light assessment to identify areas receiving adequate sunlight, and\r\n      consider planting shade-tolerant crops or using reflective materials to enhance\r\n      light availability in shaded spaces.\r\n- **Water Source**\r\n     - Ensure access to a reliable and clean water source. Options include municipal\r\n     water lines, rainwater harvesting, or nearby water bodies.\r\n     - Water quality is critical for food safety, especially if plants are consumed fresh or\r\n     grown hydroponically.\r\n- **Soil Condition**\r\n     - In urban settings where soil may be limited or contaminated, use soilless growing\r\n     techniques like hydroponics or container gardening.\r\n     - For areas with soil, test for contaminants like heavy metals and adjust pH, nutrient\r\n     levels, and organic matter as needed.', 1, '2025-09-16 10:44:42'),
(5, 2, 'System Designs', 'Urban agriculture systems can be adapted to different spaces and needs. Below are various\r\nsystem designs, including landscaping and backyard gardening, that can be implemented in\r\nurban environments.\r\n\r\n## 1. Vertical Gardens\r\nVertical gardens are ideal for maximizing space in urban areas like balconies, walls, and small\r\nbackyards.\r\n### Examples of Vertical Gardens\r\n- **Wall-mounted planters:** Pots or containers attached to a vertical surface such as a\r\nfence, wall, or building side.\r\n- **Modular systems:** Pre-designed, stackable units that allow for efficient use of\r\nvertical space, often with slots for multiple plants.\r\n- **Living walls (green walls):** Vertical panels with integrated irrigation systems,\r\nsupporting climbing plants or small crops.\r\n- **Benefits**\r\n     - **Space-efficient:** Makes use of unused vertical space.\r\n     - **Aesthetic appeal:** Adds greenery to urban environments, improving the visual\r\n     environment.\r\n     - **Accessibility:** Easy maintenance and harvesting from elevated structures.\r\n\r\n## 2. Rooftop Gardens\r\nRooftops present an untapped resource for growing food in urban areas. Rooftop gardens can\r\nprovide a space for growing vegetables, fruits, and herbs while also offering environmental\r\nbenefits.\r\n\r\n**Key Design Features**\r\n     - **Lightweight soil media:** To avoid overloading the roof structure, use materials\r\n     like cocopeat, perlite, and vermiculite that are lighter than traditional soil.\r\n     - **Drainage systems:** Proper drainage is crucial to prevent waterlogging and leaks.\r\n     Using drainage mats, sloped beds, or built-in drainage systems ensures water\r\n     flows away from the roof.\r\n     - **Raised beds and containers:** Use raised beds or modular containers to make\r\n     gardening more manageable, provide structure, and optimize space.\r\n\r\n- **Advantages**\r\n     - **Urban heat island mitigation:** Rooftop gardens cool buildings and reduce the\r\n     surrounding heat, decreasing the need for air conditioning.\r\n     - **Insulation:** Green roofs provide insulation, reducing energy consumption in the\r\n     building below.\r\n\r\n## 3. Hydroponics and Aquaponics\r\nThese soilless techniques can be especially beneficial in urban environments where soil space is\r\nlimited or of poor quality.\r\n\r\n### Hydroponics\r\n- **Soilless cultivation:** In hydroponics, plants grow in a nutrient-rich water solution.  \r\n- **Example:** A small-scale system using PVC pipes arranged in tiers, where water\r\n  flows through the pipes, nourishing the plants.  \r\n- **Benefits:** Hydroponics requires less water than traditional farming and can be\r\n  easily scaled for small urban spaces.  \r\n\r\n### Aquaponics\r\n- **Integrated system:** Combines hydroponics and fish farming, where fish waste\r\n  provides nutrients for plants, and plants help filter and purify the water for the\r\n  fish.  \r\n- **Example:** A small backyard system with a fish tank, plant grow beds, and a water\r\n  circulation system.  \r\n- **Benefits:** Sustainable food production that conserves water and reduces waste.', 2, '2025-09-16 12:14:26'),
(6, 2, 'System Designs (Part 2)', '##4. Landscaping for Urban Agriculture:\r\nLandscaping can be a functional and aesthetic addition to urban agriculture, enhancing both the\r\nbeauty and sustainability of the space.\r\n- **Examples of Landscaping Elements**\r\n     - **Edible landscapes:** Using ornamental plants that are also edible, such as **herbs**\r\n     (e.g., mint, basil), **edible flowers** (e.g., nasturtiums), or **fruit trees** (e.g., dwarf\r\n     apple or citrus).\r\n     - **Pollinator gardens:** Creating spaces to attract beneficial insects like **bees** and\r\n     **butterflies**. These gardens support biodiversity and ensure the pollination of\r\n     nearby food crops.\r\n     - **Companion planting in landscapes:** Strategic planting of crops that benefit each\r\n     other, such as planting basil near tomatoes to repel pests and enhance growth.\r\n- **Benefits:**\r\n     - **Aesthetic enhancement:** Green landscapes can beautify urban spaces, creating\r\n     tranquil environments in otherwise barren areas.\r\n     - **Sustainability:** Landscaping elements, such as **rain gardens**, can help manage\r\n     stormwater runoff and improve soil health.\r\n     - **Biodiversity promotion:** Pollinator and native plant gardens foster a healthy\r\n     ecosystem in urban areas, supporting wildlife and pest control.\r\n\r\n##5. Backyard Gardening:\r\nBackyard gardens are a simple yet highly effective way to incorporate urban agriculture into\r\nresidential settings.\r\n- **Examples of Backyard Gardening:**\r\n    - **Traditional raised beds:** Use raised beds to grow vegetables like tomatoes, lettuce,\r\n     and carrots, which can be easily tended to and help prevent soil compaction.\r\n    - **Container gardens:** Use pots or containers to grow plants, especially in spaces\r\n     with limited ground area, such as patios or balconies. Containers can support\r\n     vegetables, herbs, and small fruit plants.\r\n    - **Composting systems:** Integrating composting into a backyard garden helps\r\n    recycle organic waste into nutrient-rich soil, enhancing plant health and\r\n    sustainability.\r\n- **Benefits**\r\n     - **Self-sufficiency:** Growing food in your backyard reduces reliance on external\r\n     food sources and promotes food security.\r\n     - **Improved diet:** Backyard gardens provide access to fresh, organic produce.\r\n     - **Engagement and wellness:** Gardening promotes physical activity, mental wellbeing, \r\n     and a deeper connection to nature.', 3, '2025-09-16 14:00:59'),
(7, 2, 'Resource Management in Design', 'Efficient resource use is vital to the sustainability of urban agriculture systems. \r\nRecycling and optimization are key strategies.\r\n\r\n##1. Recycling Materials\r\n- Urban agriculture can creatively use discarded materials to save costs and reduce waste.\r\n- **Examples:**\r\n     - Plastic bottles cut in half for seedling pots or vertical gardens.\r\n     - Old tires repurposed as planters for small crops.\r\n     - Wooden pallets converted into raised garden beds or vertical frames.\r\n\r\n##2. Water Optimization\r\nEfficient water management is critical in urban settings where water availability may be limited.\r\n     - **Rainwater Harvesting Systems**\r\n          - Install simple rainwater collection setups, such as barrels or tanks, connected to\r\n          rooftop gutters. Use collected rainwater for irrigation to reduce reliance on\r\n          municipal water supplies.\r\n     - **Drip Irrigation Systems**\r\n          - Drip irrigation delivers water directly to the plant roots through a network of\r\n          tubes, minimizing evaporation and runoff.\r\n          - **Example:** A rooftop garden equipped with a timer-controlled drip system for\r\n          consistent watering.\r\n\r\n## 3.Nutrient Recycling\r\n- Use organic waste like vegetable peels and garden clippings to create compost, which can\r\nbe integrated into soil or used in vermicomposting systems.\r\n- **Nutrient-rich** water from aquaponics or collected rainwater can also be reused for\r\nirrigation.', 4, '2025-09-16 14:10:22'),
(8, 3, 'Soil-Based Farming', 'Soil-based farming remains one of the most common practices in urban agriculture. Proper soil\r\nmanagement is key to optimizing plant growth and ensuring sustainable food production.\r\n\r\n## 1. Steps in Soil Preparation\r\n- **Soil Testing**\r\nBefore planting, soil testing is essential to understand the soil’s nutrient content and pH\r\nlevel.\r\n    - **pH Testing:** Soil should have a neutral pH **(6.0 to 7.0)** for most crops. If the soil is\r\n    too acidic (below 6.0), lime can be added to raise the pH. If it is too alkaline\r\n    (above 7.0), organic matter like compost can help lower the pH.\r\n    - **Nutrient Testing:** Tests for **macronutrients** (nitrogen, phosphorus, potassium) and\r\n    micronutrients (iron, calcium, magnesium) are important to determine if\r\n    additional fertilizers or amendments are needed.\r\n- **Adding Organic Matter**\r\nOrganic matter improves soil structure, increases water retention, and provides essential\r\nnutrients. Common types include:\r\n    - **Compost:** Decomposed organic material rich in nutrients.\r\n    - **Manure:** Animal waste, often from poultry, cows, or horses, that must be\r\n    composted before use to avoid introducing pathogens.\r\n    - **Cover Crops:** Planting legumes like clover or beans as green manure helps fix\r\n    nitrogen in the soil, improving fertility\r\n## 2. Crop Management\r\n\r\n- **Plant Spacing**\r\n\r\nProper spacing between plants is critical for optimizing resource use (sunlight, water, and\r\nnutrients). Crowded plants compete for these resources, leading to poor growth and\r\nincreased susceptibility to pests and diseases. Use guidelines for recommended spacing\r\nbased on the type of crops being grown.\r\n\r\n- **Mulching**\r\n\r\nMulching helps to conserve soil moisture, reduce weed growth, and improve soil quality.\r\nMulch can be organic (straw, grass clippings) or inorganic (plastic sheets). It also helps\r\nregulate soil temperature by protecting the roots from extreme heat or cold.', 1, '2025-09-17 02:55:34'),
(9, 3, 'Soilless Farming Techniques', 'Soilless farming is an innovative method for growing plants without traditional soil, using water\r\nand nutrient solutions. These techniques are particularly useful in urban settings where soil may\r\nbe limited, contaminated, or unavailable.\r\n\r\n## 1. Hydroponics\r\nHydroponics is the practice of growing plants in a water-based, nutrient-rich solution without\r\nsoil.\r\n- **Steps to Build a Deep-Water Culture System**\r\n    - **Container:** Use a container to hold water (e.g., a large plastic bin).\r\n    - **Air Pump and Air Stone:** Place an air pump at the bottom to oxygenate the water.\r\n    An air stone connected to the pump helps to distribute oxygen evenly throughout\r\n    the solution.\r\n    - **Net Pots and Grow Media:** Use net pots to hold the plants. Place a growing\r\n    medium (such as expanded clay pellets or perlite) inside the net pots to support\r\n    the plant roots.\r\n   - **Nutrient Solution:** Mix hydroponic nutrients with water. This solution provides\r\n    essential nutrients like nitrogen, phosphorus, potassium, calcium, and magnesium.\r\n    - **Water Circulation:** Ensure water circulates through the system to allow plants to\r\n    absorb nutrients efficiently. In a Deep-Water Culture (DWC) system, plants\' roots\r\n    are submerged in the nutrient solution, which they can directly absorb.\r\n\r\n- **Nutrient Requirements for Hydroponic Vegetables**\r\n\r\nHydroponic plants require a balanced nutrient solution containing:\r\n    - **Macronutrients:** Nitrogen (N), Phosphorus (P), Potassium (K), Calcium (Ca),\r\n    Magnesium (Mg), and Sulfur (S).\r\n    - **Micronutrients:** Iron (Fe), Manganese (Mn), Boron (B), Zinc (Zn), Copper (Cu),\r\n    Molybdenum (Mo), and Chlorine (Cl).\r\n    - Regular monitoring and adjusting of the pH (usually **between 5.5 and 6.5**) and\r\n    nutrient levels is necessary to ensure optimal plant growth.\r\n\r\n## 2. Aeroponics\r\nAeroponics is a soilless growing technique where plant roots are suspended in air and misted\r\nwith a nutrient solution.\r\n\r\n- **Mist System**\r\n\r\n    A mist or fine spray is used to deliver nutrients and water directly to the plant roots. The\r\n    mist allows roots to absorb moisture and nutrients without being submerged in liquid.\r\n    - **Setup:** Typically involves a chamber or enclosed structure with hanging plants\r\n    and a misting system that delivers nutrient-laden water to the roots.\r\n- **Benefits of Aeroponics**\r\n    - **Faster Growth Rates:** Due to the high oxygen availability around the roots, plants\r\n    in aeroponic systems often grow faster than those in soil or hydroponic systems.\r\n    - **Water Efficiency:** Aeroponics uses less water than traditional farming methods\r\n    and hydroponics, as the mist system uses minimal amounts of water, and excess\r\n    water is often recycled.\r\n    - **Space Efficiency:** Aeroponics systems can be stacked vertically, making them\r\n    ideal for small urban spaces.', 2, '2025-09-17 03:02:21'),
(10, 3, 'Composting and Resource Recycling ( Part 1 )', 'Composting and resource recycling are critical components of urban agriculture, allowing\r\ncommunities to recycle organic waste into nutrient-rich soil amendments. This process reduces\r\nwaste sent to landfills, improves soil health, and supports sustainable food production.\r\n\r\n## Types of Composting\r\n\r\n- **1. Aerobic Composting (Requires Oxygen)** \r\n\r\nAerobic composting occurs when organic material decomposes in the presence \r\nof oxygen. It relies on microorganisms, such as bacteria and fungi, that require \r\noxygen to break down organic matter.\r\n\r\n- **Traditional Aerobic Composting**\r\n\r\n    - **Process:** Involves layering organic waste (green materials like food scraps, grass\r\n    clippings, and brown materials like leaves, straw, or cardboard) in a compost pile or bin.\r\n    The pile must be turned regularly (every few weeks) to ensure oxygen reaches all parts\r\n    and to accelerate decomposition.\r\n    - **Benefits:** Produces high-quality compost with a balance of nutrients that enriches soil.\r\n    - **Timeframe:** Typically takes 3-6 months to fully decompose, depending on pile size and\r\n    environmental factors (e.g., temperature and moisture).\r\n- **Rapid Aerobic Composting**\r\n    - **Process:** This method speeds up the composting process by maintaining a high\r\n    temperature (**50-70°C** or **122-158°F**) and regular turning of the compost pile. Adding\r\n    nitrogen-rich materials (e.g., fresh grass, manure) and ensuring proper moisture helps\r\n    create the ideal conditions for heat-loving microorganisms.\r\n    - **Benefits:** Accelerates the breakdown of organic material in a shorter time frame (2-3\r\n    weeks).\r\n    - **Considerations:** Requires frequent monitoring of temperature and moisture levels to\r\n    maintain optimal conditions.\r\n- **Hot Composting (Temperature-Controlled Method)**\r\n    - **Process:** The compost pile is kept at higher temperatures (above **55°C** or **131°F**) for a\r\n    period of time, which kills pathogens, weed seeds, and accelerates decomposition.\r\n    - **Benefits:** Results in quick decomposition and cleaner compost. Ideal for large amounts of\r\n    waste.\r\n    - **Considerations:** Needs careful management of moisture, aeration, and carbon-to-nitrogen\r\n    ratio. It can require more frequent turning.\r\n- **Bin or Tumbler Composting**\r\n    - **Process:** Using a compost bin or tumbler to contain and rotate compost. The enclosed\r\n    space retains heat and moisture, making it easier to manage the process.\r\n    - **Benefits:** More compact and neat compared to open piles. Easier to turn with a tumbler.\r\n    - **Considerations:** Can be smaller, making it less suitable for large quantities of waste.', 3, '2025-09-17 03:14:05'),
(11, 3, 'Composting and Resource Recycling ( Part 2 )', '## Types of Composting\r\n\r\n- **2. Anaerobic Composting (Without Oxygen)**\r\n\r\nAnaerobic composting takes place in low-oxygen environments. Microorganisms\r\nthat thrive without oxygen (**anaerobes**) break down the organic matter in these systems.\r\n\r\n- **Bokashi Composting**\r\n\r\n    - **Process:** A Japanese method where food scraps (including cooked food and dairy) are\r\n    fermented using a special inoculant made of beneficial microorganisms. The scraps are\r\n    placed in an airtight container, and the Bokashi mix is added to encourage fermentation.\r\n    - **Benefits:** Can compost a wider variety of organic materials (e.g., meat, dairy) compared\r\n    to other composting methods. Produces a fermented product that can be added to soil or\r\n    used as a soil amendment.\r\n    - **Considerations:** Requires a sealed container and patience for fermentation. The final\r\n    product is not fully composted and must be buried or added to a compost pile for further\r\n    decomposition.\r\n\r\n- **Pit Composting**\r\n\r\n    - **Process:** Organic waste is buried in a pit or trench where anaerobic decomposition occurs.\r\n    It is often used for large-scale organic waste management.\r\n    - **Benefits:** Simple and low-maintenance; suitable for disposing of large amounts of organic\r\n    waste.\r\n    - **Considerations:** Longer decomposition times and possible odors if not properly managed.\r\n\r\n- **Tumbler or Closed-Container Anaerobic Composting**\r\n\r\n    - **Process:** A sealed container or tumbler is used to contain organic waste, where anaerobic\r\n    conditions are maintained. Some systems incorporate the use of microorganisms or\r\n    inoculants to enhance fermentation.\r\n    - **Benefits:** More contained and odor-controlled compared to pit composting.\r\n    - **Considerations** Requires a closed system, making it more manageable but slower than\r\n    aerobic methods.', 4, '2025-09-17 03:19:41'),
(12, 3, 'Composting and Resource Recycling ( Part3 )', '## Types of Composting\r\n\r\n**3. Vermicomposting (Using Earthworms)**\r\n\r\nVermicomposting uses worms (often red wiggler worms) to break down organic waste into high quality compost. \r\nThe worms consume organic material, producing castings (worm manure) that are rich in plant nutrients.\r\n\r\n- **Process:** Create a worm bin using bedding (e.g., shredded newspaper, coconut coir) and\r\nadd food scraps like fruit and vegetable peels. The worms digest the organic waste and\r\nproduce nutrient-rich castings.\r\n- **Benefits:** The resulting vermicompost is high in essential nutrients like nitrogen,\r\nphosphorus, and potassium, which are excellent for soil health.\r\n- **Considerations:** Vermicomposting requires a controlled environment (cool, moist, and\r\ndark) and may not be suitable for large-scale composting.\r\n\r\n**4. Black Soldier Fly (BSF) Composting**\r\n\r\nBlack soldier flies are larvae of the black soldier fly (**Hermetia illucens**) and are known for their\r\nability to decompose organic waste rapidly. They are commonly used in waste-to-nutrient\r\nsystems.\r\n\r\n- **Process:** Organic waste (e.g., food scraps, manure) is placed in a container where BSF\r\nlarvae consume the waste and convert it into high-protein biomass. The larvae can later\r\nbe harvested and used as feed for animals, while the remaining composted material can\r\nbe used to enrich soil.\r\n- **Benefits:**\r\n    - Rapid decomposition of organic waste.\r\n    - Larvae are a high-protein animal feed, making the system a dual-purpose solution.\r\n    - Efficient conversion of waste into usable products.\r\n- **Considerations:** Requires a warm environment for optimal larvae growth (**25-35°C** or **77-\r\n95°F**). Careful management of the system is needed to prevent odors and ensure proper\r\nwaste flow.\r\n\r\n**5. Recycling Waste for Fertilizer Production**\r\n\r\nUsing kitchen scraps and other organic waste materials for composting and fertilizer production\r\nhelps reduce the volume of waste in landfills while creating valuable resources for urban\r\nagriculture. Simple methods like composting food scraps, yard waste, and organic materials such\r\nas coffee grounds, eggshells, and fruit peels can result in nutrient-dense compost, which can be\r\ndirectly added to soil or used as part of a vermicomposting or BSF system.', 5, '2025-09-17 03:25:48'),
(13, 4, 'Common Pests and Diseases in Urban Farming ( Part 1 )', '## Pests \r\n\r\n**Pests** are organisms that damage crops by feeding on them, transmitting diseases, or\r\ndisturbing their growth processes. Here are some common pests found in urban farming,\r\nespecially in vegetables:\r\n\r\n- **Aphids:** Small, soft-bodied insects that suck sap from plants, leading to stunted growth,\r\ncurled leaves, and reduced plant vitality. Common on leafy greens like lettuce and\r\nspinach.\r\n- **Spider Mites:** Tiny arachnids that cause speckled, discolored, and damaged leaves,\r\nparticularly on crops like tomatoes, peppers, and beans. They thrive in hot, dry\r\nconditions.\r\n- **Whiteflies:** Small, flying insects that feed on plant sap, often found on the undersides of\r\nleaves. They can transmit viral diseases and lead to yellowing and wilting.\r\n- **Slugs and Snails:** These pests feed on leaves, stems, and fruit, creating irregular holes and\r\nsilvery trails on plants like lettuce, cabbage, and other leafy vegetables.\r\n- **Cabbage Worms (Imported Cabbageworm):** The larvae of a white butterfly, these worms\r\nchew on the leaves of cabbage, cauliflower, and other brassicas, damaging the plant.\r\n- **Thrips:** Tiny, winged insects that cause streaking or silvering on leaves and flowers,\r\nparticularly affecting tomatoes, peppers, and strawberries.\r\n- **Root-Knot Nematodes:** Microscopic worms that infect the roots of plants like tomatoes\r\nand peppers, causing swelling or galls, leading to reduced water and nutrient uptake.', 1, '2025-09-17 03:32:53'),
(14, 4, 'Common Pests and Diseases in Urban Farming ( Part 2 )', '## Diseases\r\n\r\n**Plant diseases** are caused by various pathogens like fungi, bacteria, viruses, and\r\nnematodes. Common vegetable diseases in urban agriculture include:\r\n\r\n- **Powdery Mildew:** A fungal disease that appears as white, powdery spots on leaves. It\r\naffects many crops like cucumbers, squash, and tomatoes, reducing photosynthesis and\r\nweakening plants.\r\n- **Bacterial Blight:** Caused by bacteria, this disease results in water-soaked lesions on\r\nleaves, stems, and fruits. It is common in tomatoes, peppers, and beans and can spread\r\nrapidly in humid conditions.\r\n- **Downy Mildew:** A fungal-like disease that affects plants like lettuce, spinach, and\r\ncucumbers. It causes yellowing and distortion of leaves, especially under high humidity.\r\n- **Fusarium Wilt:** A soil-borne fungal disease that causes wilting, yellowing of leaves, and\r\ndeath of the plant. Common in tomatoes, eggplants, and peppers.\r\n- **Tomato Blight (Early and Late Blight):** Fungal infections that cause dark, water-soaked\r\nlesions on leaves, stems, and fruits. Early blight affects older foliage, while late blight\r\nspreads rapidly under wet, cool conditions.\r\n- **Root Rot (Phytophthora or Pythium):** Caused by water mold, root rot affects plants in\r\npoorly drained soils and leads to yellowing, wilting, and eventual death of the plant.', 2, '2025-09-17 03:34:25'),
(15, 4, 'Management Practices for Pest and Disease Control ( Part 1 )', '## Biological Control\r\n\r\nBiological control involves the use of natural enemies of pests to regulate\r\ntheir populations. This is an eco-friendly way of pest management that avoids chemical\r\npesticides.\r\n\r\n- **Beneficial Insects**\r\n\r\n    - **Ladybugs (Ladybird Beetles):** Natural predators of aphids, mealybugs, and other softbodied pests.\r\n    - **Lacewing Larvae:** Lacewings are excellent predators of aphids, thrips, and mealybugs.\r\n    Their larvae can consume large numbers of pests, making them effective in pest control\r\n    for crops like lettuce, tomatoes, and other leafy vegetables.\r\n    - **Predatory Mites:** These mites feed on pest mites, helping to control spider mite\r\n    populations in crops like beans and cucumbers.\r\n    - **Parasitic Wasps:** Wasps like Trichogramma spp. parasitize the eggs of pest insects, such\r\n    as moths, and can help control caterpillar pests.\r\n\r\n- **Entomopathogenic Fungi and Bacteria**\r\n\r\n    - **Beauveria bassiana:** A naturally occurring fungus that targets a wide range of pests,\r\n    including aphids, whiteflies, and beetles. When applied to crops, it infects and kills the\r\n    pests without harming beneficial insects or plants.\r\n    - **Metarhizium anisopliae:** Another beneficial fungus that infects and kills insects such as\r\n    termites, beetles, and ants. It works similarly to Beauveria bassiana and can be applied to\r\n    crops to control pests like root weevils and other soil-borne insects.\r\n    - **Bacillus thuringiensis (Bt):** A bacterium that controls caterpillar pests by producing a\r\n    toxin that is toxic to insect larvae but harmless to humans, animals, and beneficial insects.\r\n\r\n- **Microbial Biological control agent**\r\n\r\n    - **Trichoderma spp:** This beneficial fungus is used in soil health management and to\r\n    control plant diseases like root rot caused by pathogens such as Pythium and Fusarium. It\r\n    also promotes plant growth by outcompeting harmful soil fungi, acting as a natural\r\n    biocontrol agent.', 3, '2025-09-17 03:39:40'),
(16, 4, 'Management Practices for Pest and Disease Control ( Part 2 )', '## Cultural Practices\r\n\r\nCultural practices involve managing the growing environment to reduce\r\npest and disease pressures.\r\n\r\n- **Crop Rotation:** Rotating crops each season reduces the likelihood of pests and diseases\r\nthat favor certain plant families. For instance, avoiding planting tomatoes in the same\r\nspot as last year can prevent soil-borne pathogens like Fusarium wilt.\r\n- **Intercropping:** Growing different plant species together helps confuse pests and reduces\r\npest attraction to any one crop. For example, intercropping basil with tomatoes can help\r\ndeter pests like aphids and whiteflies.\r\n- **Resistant Varieties:** Select vegetable varieties that are resistant to common pests and\r\ndiseases. For example, certain tomato varieties are resistant to late blight, while other\r\nvegetables may have natural resistance to aphids or mildew.\r\n- **Proper Spacing:** Overcrowding can create a microclimate that is conducive to disease.\r\nProper spacing ensures adequate airflow, reducing humidity levels and the risk of\r\ndiseases like powdery mildew.\r\n- **Sanitation:** Regularly remove and dispose of infected plants or leaves to prevent the\r\nspread of pests and pathogens. Clean tools and equipment to avoid cross-contamination.', 4, '2025-09-17 03:43:06'),
(17, 4, 'Management Practices for Pest and Disease Control ( Part 3 )', '## Organic Pesticides\r\n\r\nOrganic pesticides are a safer alternative to synthetic chemicals and are\r\ngenerally less toxic to humans, animals, and beneficial insects.\r\n\r\n- **Neem Oil:** Derived from the neem tree, neem oil is an organic pesticide that repels and\r\nkills pests like aphids, whiteflies, and spider mites. It works as an insect growth regulator,\r\npreventing pest larvae from maturing.\r\n- **Garlic Spray:** Garlic is a natural insect repellent. A garlic solution (blended garlic and\r\nwater) can deter aphids, whiteflies, and beetles.\r\n- **Chili Pepper Extract:** A strong spray made from hot peppers can deter a wide range of\r\npests, including aphids, caterpillars, and ants. The capsaicin in the peppers irritates pests,\r\nkeeping them away from plants.\r\n- **Diatomaceous Earth:** Made from fossilized remains of algae, diatomaceous earth is a fine\r\npowder that can be sprinkled around plants to control soft-bodied pests like slugs, snails,\r\nand aphids. It works by desiccating the pests.\r\n- **Insecticidal Soap:** Made from potassium salts, insecticidal soap is effective against softbodied \r\npests like aphids, whiteflies, and mealybugs. It disrupts the cell membranes of pests and dries them out.', 5, '2025-09-17 03:44:44'),
(18, 4, 'Integrated Pest Management (IPM) for Vegetables', 'IPM combines multiple pest management strategies to minimize the impact of pests and diseases\r\non crops.\r\n\r\n- **Monitoring:** Regularly inspect crops for signs of pests and diseases. Use tools like sticky\r\ntraps, visual checks, and soil tests to detect early infestations.\r\n- **Thresholds:** Determine pest population levels where control is needed. If pest numbers\r\nare below the threshold, no action is necessary. If they exceed the threshold, control\r\nmeasures are implemented.\r\n- **Physical Barriers:** Use row covers or netting to prevent pests like aphids and cabbage\r\nworms from accessing plants. Netting can also protect crops from flying insects like\r\nwhiteflies.\r\n- **Trap Cropping:** Grow a pest-attracting plant (e.g., marigolds for aphids or mustard for\r\ncabbage worms) near your vegetables. Pests are drawn to the trap crop, protecting your\r\nmain crops.\r\n- **Companion Planting:** Certain plants repel pests or attract beneficial insects. For example,\r\nplanting basil with tomatoes can deter whiteflies, and marigolds can help repel\r\nnematodes.', 6, '2025-09-17 03:45:44'),
(19, 5, 'Factors Influencing Crop Selection (Part 1)', '## Climate\r\n\r\nUrban microclimates are often distinct from rural areas, which can affect the growth and yield of\r\ncrops. In Baguio City, the temperate climate plays a significant role in determining which crops\r\nare best suited for cultivation\r\n\r\n- **Temperature** \r\n\r\nBaguio\'s average temperature ranges from **15°C** to **23°C**, with cooler\r\ntemperatures from December to February. This climate is favorable for growing crops\r\nthat thrive in cooler conditions, such as leafy vegetables, root crops, and certain fruiting\r\nvegetables.\r\n\r\n- **Example Crops:** Lettuce, spinach, cabbage, and carrots are well-suited for\r\nBaguio\'s cool season, whereas warm-season crops like tomatoes, peppers, and\r\ncucumbers may require sheltered areas or careful management during the cooler\r\nmonths.\r\n- **Rainfall:** The rainy season in Baguio typically lasts from May to October, with the peak\r\nof rainfall occurring between June and August. Farmers should select crops that can\r\ntolerate excess moisture or plan for appropriate drainage systems.\r\n- **Example Crops:** Crops like lettuce, spinach, and other leafy greens can handle\r\nexcess moisture, but crops like tomatoes and peppers may suffer from\r\nwaterlogging if not managed properly.\r\n- **Microclimates:** Urban environments in Baguio, such as rooftops or enclosed spaces, can\r\ncreate different microclimates. For example, rooftops can get warmer during the day but\r\nmay have cooler night temperatures, which affect plant growth. Heat-tolerant crops like\r\nokra and eggplant can benefit from the heat captured by rooftops.\r\n- **Example Crops:** Heat-tolerant crops, such as okra, eggplant, and herbs like basil\r\nand oregano, are ideal for urban farming setups with varying microclimates.', 1, '2025-09-17 03:51:35'),
(20, 5, 'Factors Influencing Crop Selection ( Part 2 )', '## Space Constraints\r\n\r\nUrban areas like Baguio City often have limited space for farming, especially in densely\r\npopulated regions. Therefore, choosing crops that can yield high results in small spaces or\r\nvertical spaces is essential.\r\n\r\n- **Vertical Farming:** Growing climbing crops like beans, peas, and tomatoes on trellises or\r\nusing vertical hydroponics can help maximize limited space.\r\n- **High-Yield, Low-Space Crops:** **Leafy vegetables** (e.g., lettuce, kale), **herbs** (e.g., basil,\r\ncilantro), and **small fruits** (e.g., strawberries, dwarf varieties of tomatoes) can be grown\r\nefficiently in compact spaces, such as containers, window boxes, and small garden beds.\r\n\r\n## Market Demand and Nutritional Value\r\n\r\nWhen selecting crops for urban farming, it\'s essential to consider local market demand and\r\nnutritional value. This ensures the crops are not only suited to local conditions but also meet\r\nconsumer needs.\r\n\r\n- **Local Restaurants and Markets:** Baguio City has a thriving food culture, with restaurants\r\nseeking fresh, local produce. Crops like basil, lettuce, tomatoes, and culinary herbs are\r\nalways in demand.\r\n- **Nutritional Value:** Prioritize crops that provide nutritional benefits, such as **leafy greens**\r\n(rich in vitamins A, C, and K) and **root vegetables** (good sources of carbohydrates and\r\nfiber).', 2, '2025-09-17 03:54:00'),
(21, 5, 'Seasonal Crop Calendars for Baguio City ( Part 1)', 'A seasonal crop calendar helps farmers plan their crops to ensure year-round production,\r\nmaximize yields, and manage pest cycles effectively. The following steps outline the process for\r\ncreating a seasonal crop calendar tailored to the specific conditions of Baguio City.\r\n\r\n## Key Steps in Planning\r\n\r\n- **1. Identify Growing Seasons Based on Local Climatic Data**\r\n    - Baguio City experiences distinct dry and wet seasons. Knowing the timing and\r\n    duration of these seasons helps determine which crops are best suited for specific\r\n    months.\r\n    - Cool season crops are typically planted during the dry months (**November to\r\n    February**), while warm season crops are planted during the warmer, wetter\r\n    months (**March to June**).\r\n- **2. Plan for Crop Rotations**\r\n    - To maintain soil health and prevent pest build-up, practice crop rotation. For\r\n    instance, after growing nitrogen-demanding crops like beans, rotate with crops\r\n    like carrots or tomatoes that don’t deplete soil nutrients as quickly.\r\n- **3. Account for Crop Maturity Periods**\r\n    - To ensure staggered harvests and minimize waste, plan for crops with varying\r\n    maturity periods. For example, plant radishes (quick-growing) alongside broccoli\r\n    (longer growing period) to harvest different crops at different times.', 3, '2025-09-17 03:58:28'),
(22, 5, 'Seasonal Crop Calendars for Baguio City (Part 2)', '## Example Crop Calendar for Baguio City\r\n\r\n- **Cool Season Crops (November–February)**\r\n\r\n    - **Leafy Vegetables:** Lettuce, spinach, kale, arugula, and cabbage are ideal for Baguio’s\r\n    cool, dry season.\r\n    - **Root Crops:** Carrots, beets, and radishes thrive in the cool weather and can be harvested\r\n    in as little as 30-60 days.\r\n    - **Cruciferous Vegetables:** Broccoli, cauliflower, and Brussels sprouts are perfect for cooler\r\n    temperatures.\r\n\r\n- **Warm Season Crops (March–June)**\r\n\r\n    - **Tomatoes:** Planting during the dry season ensures better fruit set and avoids excess rain,\r\n    which can promote fungal diseases.\r\n    - **Peppers:** Both sweet and hot peppers are suited to the warm climate and should be\r\n    planted after the last frost.\r\n    - **Cucumbers:** These crops benefit from Baguio’s warm weather and can be grown in\r\n    containers or vertical gardens.\r\n    - **Herbs:** Herbs like basil, oregano, and thyme thrive in warmer temperatures, making them\r\n    ideal for the dry months.\r\n\r\n- **Transition Period (July–October)**\r\n\r\n    - **Pests and Disease Management:** The rainy season can bring more pests and diseases. This\r\n    is a good time to grow crops that are resilient to wet conditions, such as beans, peas, and\r\n    herbs.\r\n    - **Root Vegetables:** Sweet potatoes and yams can handle some rain, making them suitable\r\n    for planting in the transition months.\r\n\r\n**Note:** These is just an example of a crop calendar. There is still no established Crop Calendar for\r\nBaguio City. Further research is needed.', 4, '2025-09-17 04:01:53'),
(23, 5, 'Techniques for Maximizing Yields Across Different Seasons', '## 1. Greenhouses and Shade Netting\r\n\r\n- In Baguio, temperature fluctuations can sometimes affect crops. Using\r\n**greenhouses** or shade nets can help manage temperature, protect plants from\r\npests, and extend the growing season.\r\n\r\n##  2. Raised Beds and Containers\r\n\r\n- Utilize **raised beds** for better soil drainage, which is especially helpful during the\r\nrainy season. **Container gardening** is another space-saving technique that allows\r\nfarmers to grow crops like tomatoes, peppers, and herbs in compact urban spaces.\r\n\r\n## 3. Water Management\r\n\r\n-  Install **drip irrigation systems** to ensure efficient water delivery during dry periods\r\nand prevent waterlogging during the rainy season.', 5, '2025-09-17 04:03:42');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `progress_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `modules`
--

CREATE TABLE `modules` (
  `module_id` int(100) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `image_path` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `modules`
--

INSERT INTO `modules` (`module_id`, `title`, `description`, `image_path`, `created_at`, `updated_at`) VALUES
(1, 'Introduction to Urban Agriculture', 'In this module, you’ll learn about the fundamentals of urban agriculture, including its definition, historical development, and key benefits. You will also explore how farming within cities supports food security, sustainability, and community well-being.', 'asd', '2025-09-16 06:58:21', '2025-09-16 06:58:21'),
(2, 'Planning and Designing Urban Agriculture Systems', 'Urban agriculture systems must be carefully planned and designed to maximize the use of\r\navailable resources, ensure sustainability, and adapt to urban constraints. This module provides a\r\ndetailed guide to site assessment, system designs, and resource management.', 'asd', '2025-09-16 10:31:50', '2025-09-16 10:31:50'),
(3, 'Techniques and Practices in Urban Agriculture', 'This module focuses on various techniques and practices essential for successful urban\r\nagriculture. It covers soil-based farming, soilless farming techniques (hydroponics and\r\naeroponics), and the importance of composting and resource recycling in urban settings.', 'asdasd', '2025-09-17 02:51:30', '2025-09-17 02:51:30'),
(4, 'Pest and Disease Management in Urban Farming', 'Effective pest and disease management is crucial for urban agriculture, where limited space and\r\nproximity to homes and communities require sustainable and environmentally friendly\r\napproaches. Integrated Pest Management (IPM) integrates biological, cultural, mechanical, and\r\nchemical controls to manage pests and diseases without harming the environment or human\r\nhealth.', 'asd', '2025-09-17 03:29:05', '2025-09-17 03:29:05'),
(5, 'Crop Selection and Calendar Planning (Baguio City Focus)', 'In urban agriculture, selecting the right crops and creating an efficient seasonal crop calendar are\r\nessential for maximizing yields, meeting market demands, and ensuring the sustainability of\r\nfarming systems. Baguio City, with its distinct climate and terrain, presents unique challenges\r\nand opportunities for crop selection. This module provides a comprehensive approach to\r\nunderstanding these factors and creating an effective crop calendar tailored to Baguio City\'s\r\nconditions.', 'asdasd', '2025-09-17 03:48:32', '2025-09-17 03:48:32');

-- --------------------------------------------------------

--
-- Table structure for table `plant`
--

CREATE TABLE `plant` (
  `plant_id` int(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `container_soil` text DEFAULT NULL,
  `watering` text DEFAULT NULL,
  `sunlight` text DEFAULT NULL,
  `tips` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant`
--

INSERT INTO `plant` (`plant_id`, `name`, `description`, `image`, `container_soil`, `watering`, `sunlight`, `tips`) VALUES
(3, 'Eggplant', 'A versatile plant for urban farming, eggplants can be grown in large pots or vertical gardens. They are heat-tolerant and produce an abundant harvest in limited spaces. Perfect for homegrown meals, eggplants bring a fresh, rich flavor to dishes like stir-fries and pasta.', '../images/eggplant.jpg', 'Large pot, nutrient-rich soil', 'Water evenly, avoid overwatering', '6-8 hours sunlight', 'Use stakes or cages for support'),
(4, 'Lettuce', 'A staple for urban farming beginners, lettuce is easy to grow in small containers or hydroponic systems. With its quick growth cycle, it provides a continuous harvest of fresh greens for salads and wraps. Lettuce thrives in partial sunlight, making it ideal for balcony or rooftop gardens.\r\n\r\n', '../images/lettuce.jpg', 'Shallow containers, loose soil', 'Water lightly and frequently', 'Partial sunlight', 'Harvest outer leaves first'),
(6, 'Tomato', 'A favorite for urban farmers, tomatoes grow well in containers, hanging baskets, or vertical trellises. They love sunlight and adapt easily to urban spaces, producing vibrant, juicy fruits. Whether cherry or beefsteak, fresh tomatoes are a rewarding addition to any urban garden.\r\n\r\n', '../images/tomato.jpg', 'Large container, loose nutrient-rich soil', 'Water deeply and consistently', '6-8 hours sunlight', 'Use stakes, cages, or trellises for support');

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reply`
--

CREATE TABLE `reply` (
  `reply_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `body` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suggestions`
--

CREATE TABLE `suggestions` (
  `suggestion_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suggestions`
--

INSERT INTO `suggestions` (`suggestion_id`, `message`, `created_at`, `status`) VALUES
(3, 'Dagdag Modules', '2024-12-04 05:05:12', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('student','admin','agriculturist','new user') NOT NULL,
  `date_created` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(10) DEFAULT 'active',
  `profile_picture` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `username`, `password`, `role`, `date_created`, `status`, `profile_picture`) VALUES
(1, 'admin', 'admin', '$2y$10$B.jf5ql45Tg/wj5YijIaZuANWPNmTtc0GGh.ZfTSckvnl.SCITgmq', 'admin', '2024-12-01 06:06:20', 'approved', NULL),
(22, 'Alexander F. Lavarias', 'alex', '$2y$10$hZuqccRaPWGGsdOEcRbte.YjPm6fnrsJiZECLU8Q//582pMOhPjN2', 'student', '2025-07-01 16:09:16', 'active', 'user_22_1751642780.jpg'),
(33, 'haha', 'haha', '$2y$10$TtHNLWXDNxBe1mnd5E247.y3rXMXb2dmRirgB6P1Cs47TILOjzQjS', 'agriculturist', '2025-09-06 01:47:06', 'active', NULL),
(35, 'hehe', 'hehe', '$2y$10$oh8kdrUJ9kVs3TbaIBhTp.GIGI6ndoNP.Y1iga/CPfnqg1Pfl8yyC', 'student', '2025-09-16 02:21:40', 'active', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD PRIMARY KEY (`progress_id`),
  ADD UNIQUE KEY `unique_user_lesson` (`user_id`,`lesson_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `modules`
--
ALTER TABLE `modules`
  ADD PRIMARY KEY (`module_id`);

--
-- Indexes for table `plant`
--
ALTER TABLE `plant`
  ADD PRIMARY KEY (`plant_id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `reply`
--
ALTER TABLE `reply`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `question_id` (`question_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `suggestions`
--
ALTER TABLE `suggestions`
  ADD PRIMARY KEY (`suggestion_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  MODIFY `progress_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `modules`
--
ALTER TABLE `modules`
  MODIFY `module_id` int(100) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `plant`
--
ALTER TABLE `plant`
  MODIFY `plant_id` int(255) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `reply`
--
ALTER TABLE `reply`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `suggestions`
--
ALTER TABLE `suggestions`
  MODIFY `suggestion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`);

--
-- Constraints for table `lesson_progress`
--
ALTER TABLE `lesson_progress`
  ADD CONSTRAINT `lesson_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `lesson_progress_ibfk_2` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`);

--
-- Constraints for table `questions`
--
ALTER TABLE `questions`
  ADD CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `reply`
--
ALTER TABLE `reply`
  ADD CONSTRAINT `reply_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`question_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reply_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
