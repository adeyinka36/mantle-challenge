The old checkout method did everything in one place. It priced the order, saved it, sent the email and wrote the log. A new customer type or a new message meant another if in that method. You could not test a price on its own, because the only path ran the database and the mailer too.

PlaceOrder finds the customer, builds the order, saves it, then tells the rest of the app. The purchaser email, the gift note, the log and the SMS are listeners on that event. The controller reads the JSON and returns the id and total. An empty cart or a bad email is a 422. A body that is not JSON is a 400.

Each discount is its own class. The sample rule runs first, so a sample stays free, even as a gift. Customer types are an enum. A missing type is still regular. An unknown type such as wholesale is rejected by the serializer, and the error text is Symfony's, which is a bit clumsy.

Gift wrap is 3.50 from GIFT_WRAP_PRICE. It is added after the discount and before the save. I took the old behaviour as the discounted total plus the wrap, so a 25 pound VIP gift is 23.50. The purchaser gets the normal priced email. The recipient gets "A gift is on its way to you." The ticket asked for no prices and did not give the words.

Trade can go below zero. The saved total stops at 0 after the wrap is added. A 5 pound trade order is 0.00. The same order as a gift is 3.00. A 25 pound trade order stays 17.50.

There is no real SMS provider. The listener calls an interface, and the tests record the message.

With more time I would count the money in pence instead of floats, so a total cannot slip by a fraction of a penny. I would also add a real SMS sender and check that the mobile looks like a phone number. The order log is still a line in a file.
