# Helium Store
Helium store is a deep modification of Opencart 3 with multiple major changes
It introduces comprehensive changes in frontend to have maximum performace, match all modern SEO requirements, support assistive technologies and screen readers, yet use minimum network payload.
No side JS libraries used to minimize code initialisation, everything is written in Vanilla JS.

## Database
Every single DB query in Helium store was tested to be as fast as possible to support multiple simultaneous clients browsing store with thouthands of products.
The DB structure changes include new primary keys, indexes, queries that minimize traffic between SQL and PHP processes. 
Narrow queries as product search, filtering and display were rewritten from scratch.
Several tables were added for new functions.

## Javascript
To the opposit of common usage of side libraries Helium Store relies on Vanilla JS written from the scratch.
The goal is to create lightweight and fast initializing code that uses native browser features.
Here are some of features included in this project
### Main menu
1. Responsive. 
2. Has assistive tehnologies markup for diasbled users, announces navigation points, supports keyboard interaction, hides closed menu block from screen readers. 
3. Best SEO results - does't hide markup from Google bot as many others.
4. Does not affect time-to-interactive metric.
5. All the data gathered from DB in single query.
### Live Search
1. Displays products with images, prices, short descriptions. 
2. Highlights search terms in text. 
3. Shows countdown to discount end if applicable. 
4. Orders products by relevance to the search term.
5. Has support for assistive technologies and keyboard interaction.
6. Works really fast!
In future update I will add search in Blog posts, Product filter pages, Categories, Product features 
### Live cart
The main part of every online store is checkout process.

### 

## CSS
All the styles were writteh from the scratch, on top of Normalize CSS. It is responsive and also doesn't manipulate content visibility not to harm SEO. 
Minimum mediaqueries used, basically for narrow and wide screens to ommit useless grids for number of screen sizes.
No monstrous CSS frameworks used, as realworld pages use 10%-20% of that code.
### Color system
Design relies heavily on CSS variables and built-in CSS calc() functions.
Calculations are used to dynamically create color schema by using HSL colors.
This means you can quickly change primary color and all other complementary and triad colors will be calculated by CSS.
This also relates to focus states, call-to-action buttons,
Transparency, animations, shadows and rounded corners also controlled in one place, so no need to search and replace multiple lines.
Every bit of design changes accordingly to global variables.
Currently minified and gzipped CSS file is under 10kb of payload.
